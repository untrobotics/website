#!/usr/bin/env bash
#
# Build the application and driver-ws images on the server and import them into
# the k3s containerd image store (no external registry needed on a single node).
# Run from the repo root, or pass the repo path as $1.
set -euo pipefail

REPO_DIR="${1:-$(cd "$(dirname "$0")/.." && pwd)}"
cd "$REPO_DIR"

# Use BuildKit: the legacy builder can serve a stale `COPY . .` layer from cache
# (it bit us once — new files silently missing from the image). BuildKit hashes
# content correctly so edited/added files always invalidate the COPY layer.
export DOCKER_BUILDKIT=1

echo "==> Building untrobotics-web:dev"
docker build -t untrobotics-web:dev -f Dockerfile .

echo "==> Building untrobotics-driver-ws:dev"
docker build -t untrobotics-driver-ws:dev -f botathon/driver-ws/Dockerfile botathon/driver-ws

echo "==> Importing images into k3s containerd"
docker save untrobotics-web:dev       | sudo k3s ctr images import -
docker save untrobotics-driver-ws:dev | sudo k3s ctr images import -

echo "==> Imported:"
sudo k3s ctr images ls | grep -E 'untrobotics-(web|driver-ws):dev' || true
