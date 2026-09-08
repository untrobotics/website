# CI/CD pipeline — how code reaches dev and prod

This is the operational reference for the deploy pipeline: what happens when you
push, how a release works, and the failure modes that have actually bitten us.
For provisioning a fresh server see `runbook.md`; for the GHCR pull-secret detail
see its "Pull from GHCR" section.

**Keep this document updated** whenever the workflow, the branch model, or the
Argo apps change (Changelog at the bottom).

---

## The model in one line

**Environment = branch.** Push to `develop` deploys **dev**; merge `develop` into
`master` deploys **prod**. There are no `deploy/prod` / `deploy/dev` branches, and
`git tag` no longer deploys anything.

| Branch   | Argo app          | Namespace          | Overlay             |
|----------|-------------------|--------------------|---------------------|
| `develop`| `untrobotics-dev` | `untrobotics-dev`  | `k8s/overlays/dev`  |
| `master` | `untrobotics-prod`| `untrobotics-prod` | `k8s/overlays/prod` |
| `master` | `mail`            | `untrobotics-mail` | `k8s/mail`          |

The three Argo `Application`s live in `k8s/argocd/applications/*.yaml` and are
applied directly (`kubectl apply -f`), not via an app-of-apply. Each has
`syncPolicy.automated.selfHeal: true` and a GitHub→Argo webhook, so a commit on
the tracked branch rolls the environment within seconds — no manual sync.

---

## What happens on a push (`.github/workflows/build-images.yml`)

1. **`detect`** classifies the changed files (`github.event.before..HEAD`):
   - anything under `discord-bot/` → rebuild the bot image
   - anything under `botathon/driver-ws/` → rebuild the driver-ws image
   - **anything else** (including `k8s/overlays/**`) → rebuild the website image

   It emits a matrix of only the images that changed, so a web-only change never
   rebuilds (or spuriously fails on) the bot / driver-ws images.

2. **`build`** builds each image in the matrix and pushes it to GHCR, tagged with
   (among others) an immutable `sha-<short>` tag.

3. **`deploy-dev`** (only `refs/heads/develop`, matrix non-empty): pins the freshly
   built `sha-<short>` tags into `k8s/overlays/dev/kustomization.yaml` and commits
   that straight back to `develop` as `"dev: auto-deploy <sha> [skip ci]"`. Argo
   (tracking `develop`) rolls dev.

4. **`deploy-prod`** (only `refs/heads/master`, matrix non-empty): same, into
   `k8s/overlays/prod/kustomization.yaml`, committed to `master` as
   `"prod: auto-deploy <sha> [skip ci]"`. Argo (tracking `master`) rolls prod.
   Then it runs the **URW-46 sync** (below).

The auto-pin commit carries `[skip ci]` so it doesn't re-trigger the workflow
(no deploy loop). The image is built once; dev/prod only re-point at its sha tag.

### Why a PAT (`DEVELOP_SYNC_PAT`)

Both `develop` and `master` are protected (no force-push). The built-in
`github-actions[bot]` is not an admin, so it cannot push either branch. The
pin/sync steps therefore check out with `secrets.DEVELOP_SYNC_PAT` — a
fine-grained PAT owned by admin `sebastian-king` (Contents + Workflows RW on
`untrobotics/website`). It pushes **non-force**:

- `master` has a `bypass_pull_request_allowances` entry for `sebastian-king`, so
  the admin PAT bypasses the "PR required" rule.
- `develop` has `enforce_admins: false`, so an admin bypasses the PR rule there.
- Neither branch allows force-push, and the pipeline never needs it (every push
  descends from the current branch tip).

The token also lives on the server at `~/.gh-pat-untr-tok`. If it expires, the
pin/sync steps fail to push (the build still succeeds) — rotate it and update the
repo secret `DEVELOP_SYNC_PAT`.

### URW-46: keeping `develop` from drifting behind `master`

`master` accumulates the `[skip ci]` prod-pin commits (and any direct-to-master
hotfixes). After a prod deploy, `deploy-prod`'s final step merges `master` back
into `develop` (`"sync: merge master into develop … [skip ci]"`) so the next
`develop → master` release stays clean. A merge conflict is logged as a
`::warning::` and aborted — it is **non-fatal**, because a release must never fail
just because the develop-sync needs a hand. If you see that warning, reconcile
`develop` manually.

---

## Releasing to prod

A release is just: **merge `develop` into `master`.** Use the helper so the merge
is done the one safe way:

```sh
bash scripts/cutover-e2e-merge.sh
```

It checks out `master`, merges `develop` with **`--no-ff`** and a clean message
(`"release: deploy develop -> prod (<date>)"`), and pushes to `master`. CI's
`deploy-prod` then builds, pins, and Argo rolls prod.

> **`--no-ff` is load-bearing — do not "simplify" it to a plain merge.** See the
> silent-skip failure mode below.

To watch it land:

```sh
gh run list --workflow=build-images.yml -L 5
ssh ubuntu@dev2.untrobotics.com \
  'sudo kubectl -n untrobotics-prod get pods -w'   # or: argocd app get untrobotics-prod
```

---

## Failure modes we have actually hit

### 1. A release "spawns no CI run" → prod silently not deployed  *(systemic; fixed)*

**Symptom:** you merge `develop → master`, but no workflow run appears
(`gh run list` shows nothing new; the commit has **zero check-suites**), so
`deploy-prod` never runs and prod is not updated. No error anywhere.

**Cause:** `develop`'s tip is almost always a CI auto-pin commit
(`"… auto-deploy … [skip ci]"` or the URW-46 `"sync: … [skip ci]"`). A
**fast-forward** release makes that `[skip ci]` commit `master`'s new HEAD, and
**GitHub honours `[skip ci]` on the head commit of a push by skipping the entire
workflow.** A skipped push produces no check-suite at all — identical on the API
to a genuine delivery drop, which is what made this look random.

**Fix (already in place):** `scripts/cutover-e2e-merge.sh` merges with `--no-ff`
and an explicit clean message, so the pushed head is always a merge commit with no
skip token. `detect` diffs `github.event.before..HEAD`, so the merge commit still
yields the full `develop` diff → non-empty matrix → `deploy-prod` runs. **Always
release via the script**; a hand-typed `git merge develop` can fast-forward and
re-introduce this.

**If it ever recurs** (e.g. someone released by hand): push one empty commit to
give `master` a fresh clean head —
`git commit --allow-empty -m "ci: re-fire prod release" && git push origin HEAD:master`.

### 2. A single ordinary push spawns no run  *(rare; GitHub-side)*

**Symptom:** one push to `develop`/`master` with an ordinary commit message (no
`[skip ci]`) produces zero check-suites, while pushes before and after it work.

**Cause:** a genuine GitHub event-delivery drop. Rare, not reproducible, unrelated
to failure mode 1. (Seen once: `develop` push `5c102769`, 2026-09-08.)

**Fix:** re-fire with an empty commit, as above. If it happens repeatedly in a
short window, check <https://www.githubstatus.com> for an Actions/webhook incident
before digging further.

### 3. Non-fast-forward push rejected on `develop`

**Symptom:** your `git push origin develop` is rejected as non-fast-forward.

**Cause:** CI's `[skip ci]` pin commit advanced `origin/develop` after you last
fetched.

**Fix:** `git fetch origin && git rebase origin/develop && git push`. (Do not
force-push — protection forbids it and you don't need it.)

### 4. `ImagePullBackOff` on a fresh deploy

The GHCR pull secret (`ghcr-pull`) PAT expired. See the ghcr-pull note in
`k8s/overlays/prod/kustomization.yaml` and `runbook.md`.

---

## Diagnosing "did my push deploy?"

```sh
# Did GitHub even create a workflow run / check-suite for the commit?
gh run list --workflow=build-images.yml -L 10
gh api repos/untrobotics/website/commits/<sha>/check-suites \
  --jq '.check_suites[] | "\(.app.slug) \(.status) \(.conclusion)"'
#   → rows shown  = CI ran (open the run for detail)
#   → nothing     = the push was skipped ([skip ci] head) or dropped (see modes 1–2)

# What image is each environment actually running?
grep newTag k8s/overlays/prod/kustomization.yaml   # or overlays/dev
ssh ubuntu@dev2.untrobotics.com \
  'sudo kubectl -n untrobotics-prod get deploy -o jsonpath="{..image}"; echo'
```

---

## Changelog

- **2026-09-08 — documented the env=branch model (URW-35).** Captured the current
  pipeline: push→dev / merge→prod, `DEVELOP_SYNC_PAT` sha-pinning, URW-46
  master→develop sync, deleted `deploy/*` branches. Fixed the release script to
  merge `--no-ff` after finding that fast-forward releases onto a `[skip ci]` tip
  were silently skipping the prod deploy (failure mode 1).
