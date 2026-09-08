#!/usr/bin/env bash
# End-to-end test / release: merge develop -> master (non-force, descends from
# master). This is the new release action. On the resulting master push, CI's
# deploy-prod job builds the changed images, pins their sha tags into
# k8s/overlays/prod on master ([skip ci]), and Argo rolls prod.
#
# The prod overlay's live tags (v1.1.43 etc., set on master in step 2) survive
# the merge (develop never changed them), then deploy-prod re-pins whatever it
# rebuilt to a sha tag. App behavior is unchanged for this test.
#
# --no-ff is REQUIRED, not cosmetic. develop's tip is almost always a CI auto-pin
# commit ("dev: auto-deploy … [skip ci]" / "sync: … [skip ci]"). A fast-forward
# release would make that [skip ci] commit master's HEAD, and GitHub honours
# [skip ci] on a push's head commit by skipping the WHOLE workflow — so deploy-prod
# would never run and prod would silently not deploy. Forcing a merge commit with a
# clean message guarantees the pushed head has no skip token, so CI always fires.
set -euo pipefail
cd "$(git rev-parse --show-toplevel)"
git fetch origin -q
git checkout -B _rel origin/master
git merge --no-ff --no-edit -m "release: deploy develop -> prod ($(date +%F))" origin/develop
echo "--- prod overlay tags after merge (should still be v1.1.43 / v1.1.40 / v1.0.96) ---"
grep -E 'newTag:' k8s/overlays/prod/kustomization.yaml
git push origin _rel:master
git checkout develop
git branch -D _rel
echo "==> merged develop->master. Watch CI: deploy-prod builds, pins sha to master [skip ci], Argo deploys."
