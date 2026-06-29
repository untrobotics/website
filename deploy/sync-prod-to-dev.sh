#!/usr/bin/env bash
#
# Refresh the DEV database from PROD. ONE-WAY: prod is only ever read.
# Use to reset dev or reproduce a prod issue.
#
#   deploy/sync-prod-to-dev.sh            # anonymized refresh (safe default)
#   deploy/sync-prod-to-dev.sh --raw      # copy real data verbatim (PII included)
#   deploy/sync-prod-to-dev.sh --yes      # skip the confirmation prompt
#
# Both environments must be in-cluster MySQL (post dev/prod migration). Override
# the namespaces with PROD_NS / DEV_NS if yours differ.
set -euo pipefail
export KUBECONFIG="${KUBECONFIG:-/etc/rancher/k3s/k3s.yaml}"

PROD_NS="${PROD_NS:-untrobotics-prod}"
DEV_NS="${DEV_NS:-untrobotics-dev}"
DB="${DB:-untrobotics}"
HERE="$(cd "$(dirname "$0")" && pwd)"

ANON=1; YES=0
for a in "$@"; do case "$a" in
  --raw) ANON=0 ;;
  --yes|-y) YES=1 ;;
  *) echo "unknown arg: $a"; exit 2 ;;
esac; done

# --- Hard safety guards: dev must never be prod ------------------------------
if [ "$PROD_NS" = "$DEV_NS" ]; then echo "refusing: PROD_NS == DEV_NS"; exit 1; fi
case "$DEV_NS" in *prod*) echo "refusing: DEV_NS '$DEV_NS' looks like prod"; exit 1 ;; esac

prod_pod=$(kubectl -n "$PROD_NS" get pod -l app=mysql -o jsonpath='{.items[0].metadata.name}' 2>/dev/null || true)
dev_pod=$(kubectl  -n "$DEV_NS"  get pod -l app=mysql -o jsonpath='{.items[0].metadata.name}' 2>/dev/null || true)
[ -n "$prod_pod" ] || { echo "no mysql pod in PROD_NS '$PROD_NS'"; exit 1; }
[ -n "$dev_pod"  ] || { echo "no mysql pod in DEV_NS '$DEV_NS'"; exit 1; }

prod_root=$(kubectl -n "$PROD_NS" get secret mysql-secrets -o jsonpath='{.data.MYSQL_ROOT_PASSWORD}' | base64 -d)
dev_root=$(kubectl  -n "$DEV_NS"  get secret mysql-secrets -o jsonpath='{.data.MYSQL_ROOT_PASSWORD}' | base64 -d)

echo "Refresh DEV '$DEV_NS' from PROD '$PROD_NS'  (anonymize=$ANON)"
echo "This DROPS and replaces the '$DB' database in DEV."
if [ "$YES" != 1 ]; then
  read -r -p "Type 'yes' to continue: " c
  [ "$c" = "yes" ] || { echo "aborted"; exit 1; }
fi

DUMP="$(mktemp)"
trap 'rm -f "$DUMP"' EXIT

echo "==> dumping prod (read-only snapshot)"
kubectl -n "$PROD_NS" exec -i "$prod_pod" -- sh -c \
  "exec mysqldump --single-transaction --skip-lock-tables --routines --triggers -uroot -p'$prod_root' '$DB'" > "$DUMP"

echo "==> resetting dev database"
kubectl -n "$DEV_NS" exec -i "$dev_pod" -- sh -c \
  "exec mysql -uroot -p'$dev_root' -e \"DROP DATABASE IF EXISTS \\\`$DB\\\`; CREATE DATABASE \\\`$DB\\\`;\""

echo "==> loading into dev"
kubectl -n "$DEV_NS" exec -i "$dev_pod" -- sh -c "exec mysql -uroot -p'$dev_root' '$DB'" < "$DUMP"

if [ "$ANON" = 1 ]; then
  echo "==> anonymizing PII"
  kubectl -n "$DEV_NS" exec -i "$dev_pod" -- sh -c "exec mysql -uroot -p'$dev_root' '$DB'" < "$HERE/anonymize-dev.sql"
  # Set every dev user's password to "password" for easy testing (hash via the web pod's PHP).
  web_pod=$(kubectl -n "$DEV_NS" get pod -l app=web -o jsonpath='{.items[0].metadata.name}' 2>/dev/null || true)
  if [ -n "$web_pod" ]; then
    hash=$(kubectl -n "$DEV_NS" exec "$web_pod" -- php -r 'echo password_hash("password", PASSWORD_BCRYPT, ["cost"=>12]);')
    kubectl -n "$DEV_NS" exec -i "$dev_pod" -- sh -c \
      "exec mysql -uroot -p'$dev_root' '$DB' -e \"UPDATE users SET password='$hash';\""
    echo "    all dev users now log in with password: password"
  fi
fi

echo "==> done: dev refreshed from prod."
