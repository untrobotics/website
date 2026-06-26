#!/usr/bin/env bash
#
# Create secrets + the MySQL schema ConfigMap, then apply the k8s manifests.
# Run after install-k3s.sh and build-and-load-images.sh. Run from repo root or
# pass the repo path as $1.
set -euo pipefail

REPO_DIR="${1:-$(cd "$(dirname "$0")/.." && pwd)}"
cd "$REPO_DIR"
export KUBECONFIG="${KUBECONFIG:-/etc/rancher/k3s/k3s.yaml}"

echo "==> Namespace"
kubectl apply -f k8s/namespace.yaml

echo "==> Secrets"
# Real secret values live in deploy/secrets.env (gitignored). If absent, generate
# dev defaults (random DB password + salts; third-party keys left blank, which
# config.docker.php treats as disabled).
SECRETS_ENV="deploy/secrets.env"
if [[ ! -f "$SECRETS_ENV" ]]; then
  echo "    $SECRETS_ENV not found -> generating dev defaults"
  DBPASS="$(openssl rand -hex 16)"
  cat > "$SECRETS_ENV" <<EOF
# Generated dev secrets. Edit to add real third-party keys as needed.
DATABASE_PASSWORD=$DBPASS
MYSQL_PASSWORD=$DBPASS
MYSQL_ROOT_PASSWORD=$(openssl rand -hex 16)
HASH_SALT=$(openssl rand -hex 16)
API_SECRET=$(openssl rand -hex 16)
EOF
fi
# shellcheck disable=SC1090
set -a; source "$SECRETS_ENV"; set +a

kubectl -n untrobotics create secret generic web-secrets \
  --from-literal=DATABASE_PASSWORD="${DATABASE_PASSWORD:-}" \
  --from-literal=HASH_SALT="${HASH_SALT:-}" \
  --from-literal=API_SECRET="${API_SECRET:-}" \
  --dry-run=client -o yaml | kubectl apply -f -

kubectl -n untrobotics create secret generic mysql-secrets \
  --from-literal=MYSQL_ROOT_PASSWORD="${MYSQL_ROOT_PASSWORD:-}" \
  --from-literal=MYSQL_DATABASE=untrobotics \
  --from-literal=MYSQL_USER=untrobotics-web \
  --from-literal=MYSQL_PASSWORD="${MYSQL_PASSWORD:-}" \
  --dry-run=client -o yaml | kubectl apply -f -

echo "==> MySQL schema ConfigMap (schema only, no data)"
kubectl -n untrobotics create configmap mysql-initdb \
  --from-file=01-schema.sql=docker/mysql/initdb/01-schema.sql \
  --dry-run=client -o yaml | kubectl apply -f -

echo "==> Applying manifests"
kubectl apply -k k8s/

echo "==> Rollout"
kubectl -n untrobotics rollout status statefulset/mysql --timeout=240s || true
kubectl -n untrobotics rollout status deploy/web --timeout=240s || true
kubectl -n untrobotics get pods -o wide
