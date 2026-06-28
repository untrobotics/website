#!/usr/bin/env bash
#
# Install cert-manager and the Let's Encrypt ClusterIssuers, so the ingress can
# get a real TLS certificate for dev2.untrobotics.com via HTTP-01.
# Run on the server after install-k3s.sh. Idempotent.
set -euo pipefail

export KUBECONFIG=/etc/rancher/k3s/k3s.yaml
REPO_DIR="${REPO_DIR:-/opt/untrobotics/website}"

# Resolve the latest cert-manager release unless pinned via CM_VERSION.
CM_VERSION="${CM_VERSION:-$(curl -fsSL https://api.github.com/repos/cert-manager/cert-manager/releases/latest \
  | grep -oE '"tag_name":[[:space:]]*"[^"]+"' | grep -oE 'v[0-9.]+' | head -1)}"
echo "==> Installing cert-manager ${CM_VERSION}"
kubectl apply -f "https://github.com/cert-manager/cert-manager/releases/download/${CM_VERSION}/cert-manager.yaml"

echo "==> Waiting for cert-manager to be ready"
kubectl -n cert-manager rollout status deploy/cert-manager --timeout=180s
kubectl -n cert-manager rollout status deploy/cert-manager-webhook --timeout=180s
kubectl -n cert-manager rollout status deploy/cert-manager-cainjector --timeout=180s

echo "==> Applying Let's Encrypt ClusterIssuers"
# Retry briefly: the validating webhook can take a moment to accept connections.
for i in 1 2 3 4 5; do
  if kubectl apply -f "${REPO_DIR}/k8s/issuers.yaml"; then break; fi
  echo "   webhook not ready yet, retrying ($i)..."; sleep 10
done

echo "==> ClusterIssuers:"
kubectl get clusterissuers
