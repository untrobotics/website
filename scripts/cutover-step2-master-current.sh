#!/usr/bin/env bash
# Cutover step 2 (v3, non-force): bring `master` up to the CURRENT live prod state.
#
# master is stuck at v1.1.11; live prod (deploy/prod) is web v1.1.43 / bot
# v1.1.40 / driver v1.0.96. Before repointing Argo prod from deploy/prod to
# master, master must equal what's running, else selfHeal rolls prod back.
#
# master protection forbids force-push, but sebastian-king may push directly
# (bypass_pull_request_allowances) as long as it's a NON-force (descends from
# master) push. So we build ON TOP of master: merge develop preferring its
# content, force the tree to equal develop's, then pin the live prod tags.
# Result: k8s/ manifests == live prod; parent chain descends from master -> a
# normal push. [skip ci] avoids a build. SAFE: Argo prod still tracks
# deploy/prod here, so prod does not change; this only stages master.
set -euo pipefail
cd "$(git rev-parse --show-toplevel)"

git fetch origin
git checkout -B _master_cutover origin/master

git merge -X theirs --no-commit origin/develop || true
git checkout origin/develop -- .           # tree := develop's (stages tracked files)

python - <<'PY'
import re
p = 'k8s/overlays/prod/kustomization.yaml'
t = open(p, encoding='utf-8').read()
def setg(t, name, tag):
    return re.sub(r'(- name: ' + re.escape(name) + r'\n(?:\s+.*\n)*?\s+newTag: )"[^"]*"',
                  r'\g<1>"' + tag + '"', t)
t = setg(t, 'untrobotics-web',         'v1.1.43')
t = setg(t, 'untrobotics-driver-ws',   'v1.0.96')
t = setg(t, 'untrobotics-discord-bot', 'v1.1.40')
open(p, 'w', encoding='utf-8').write(t)
PY

git add -u                                 # stage tracked changes only (not the untracked helper script)

echo "--- prod overlay tags now: ---"
grep -E 'newTag:' k8s/overlays/prod/kustomization.yaml

git commit -qm "cutover: master := develop @ live prod tags (web v1.1.43 / bot v1.1.40 / driver v1.0.96) [skip ci]"

echo "--- SANITY: deployed manifests (k8s/) must be IDENTICAL to live prod (deploy/prod) ---"
if [ -n "$(git diff --stat origin/deploy/prod _master_cutover -- k8s/)" ]; then
  echo "!! k8s/ manifests differ from live prod — ABORTING, nothing pushed:"
  git diff --stat origin/deploy/prod _master_cutover -- k8s/
  git checkout develop; git branch -D _master_cutover
  exit 1
fi
echo "    (k8s/ identical — good)"

echo "--- pushing master (non-force, descends from master) ---"
git push origin _master_cutover:master

git checkout develop
git branch -D _master_cutover
echo "==> master now == current prod (no prod impact yet; Argo still on deploy/prod)."
