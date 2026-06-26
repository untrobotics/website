#!/usr/bin/env bash
#
# Prepare a fresh Ubuntu 24.04 LTS server and install single-node k3s plus
# ingress-nginx. Idempotent-ish: safe to re-run. Run as root (or with sudo) on
# the target server.
#
# See deploy/runbook.md for the full procedure and troubleshooting notes.
set -euo pipefail

INGRESS_NGINX_VERSION="${INGRESS_NGINX_VERSION:-controller-v1.11.3}"

echo "==> [1/5] Base packages"
export DEBIAN_FRONTEND=noninteractive
apt-get update -y
apt-get upgrade -y
apt-get install -y curl ca-certificates git docker.io openssl
systemctl enable --now docker

echo "==> [2/5] Firewall (ufw)"
# Allow SSH first so we never lock ourselves out, then web + k3s API, and the
# k3s pod/service CIDRs so cluster networking is not blocked.
if command -v ufw >/dev/null 2>&1; then
  ufw allow OpenSSH
  ufw allow 80/tcp
  ufw allow 443/tcp
  ufw allow 6443/tcp
  ufw allow from 10.42.0.0/16
  ufw allow from 10.43.0.0/16
  ufw --force enable
fi

echo "==> [3/5] Install k3s (Traefik disabled; we use ingress-nginx)"
# --write-kubeconfig-mode 644 lets the non-root 'ubuntu' user read the kubeconfig.
curl -sfL https://get.k3s.io | sh -s - \
  --disable traefik \
  --write-kubeconfig-mode 644

export KUBECONFIG=/etc/rancher/k3s/k3s.yaml

echo "==> [4/5] Wait for node Ready"
until kubectl get nodes >/dev/null 2>&1; do sleep 2; done
kubectl wait --for=condition=Ready node --all --timeout=180s

echo "==> [5/5] Install ingress-nginx"
kubectl apply -f "https://raw.githubusercontent.com/kubernetes/ingress-nginx/${INGRESS_NGINX_VERSION}/deploy/static/provider/cloud/deploy.yaml"
kubectl -n ingress-nginx rollout status deploy/ingress-nginx-controller --timeout=240s

echo "==> Done."
kubectl get nodes -o wide
