#!/usr/bin/env bash
#
# Install Argo CD (GitOps UI) and expose it at argocd.untrobotics.com.
# Run on the server after install-k3s.sh + install-tls.sh. Idempotent.
set -euo pipefail

export KUBECONFIG=/etc/rancher/k3s/k3s.yaml
REPO_DIR="${REPO_DIR:-/opt/untrobotics/website}"

ARGOCD_VERSION="${ARGOCD_VERSION:-$(curl -fsSL https://api.github.com/repos/argoproj/argo-cd/releases/latest \
  | grep -oE '"tag_name":[[:space:]]*"[^"]+"' | grep -oE 'v[0-9.]+' | head -1)}"
echo "==> Installing Argo CD ${ARGOCD_VERSION}"
kubectl create namespace argocd --dry-run=client -o yaml | kubectl apply -f -
# Server-side apply: the applicationsets CRD is too large for client-side apply's
# last-applied-configuration annotation (>256KB).
kubectl apply --server-side --force-conflicts -n argocd \
  -f "https://raw.githubusercontent.com/argoproj/argo-cd/${ARGOCD_VERSION}/manifests/install.yaml"

# Terminate TLS at the ingress (run argocd-server in insecure/HTTP mode behind it).
echo "==> Configuring argocd-server for ingress TLS termination"
kubectl -n argocd patch configmap argocd-cmd-params-cm --type merge -p '{"data":{"server.insecure":"true"}}'
kubectl -n argocd rollout restart deploy/argocd-server

echo "==> Waiting for Argo CD to be ready"
kubectl -n argocd rollout status deploy/argocd-server --timeout=300s
kubectl -n argocd rollout status deploy/argocd-repo-server --timeout=300s

echo "==> Ingress + observe-only Application"
kubectl apply -f "${REPO_DIR}/k8s/argocd/ingress.yaml"
kubectl apply -f "${REPO_DIR}/k8s/argocd/application.yaml"

echo "==> Initial admin password (user: admin):"
kubectl -n argocd get secret argocd-initial-admin-secret -o jsonpath='{.data.password}' | base64 -d; echo
echo "==> Browse https://c.untrobotics.com once its DNS A record + cert exist."
