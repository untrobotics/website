# Deploying the UNT Robotics site to a new server (k3s)

This runbook provisions a **fresh Ubuntu 24.04 LTS** server into a single-node
Kubernetes (k3s) host running the website stack: the PHP/Apache app, an
in-cluster MySQL, and the botathon WebSocket relay.

Everything here is in git so a replacement server is reproducible. **Keep this
document updated** whenever a step changes or a problem is solved (see the
Changelog at the bottom).

---

## 0. Prerequisites

- A fresh Ubuntu 24.04 LTS server with your SSH **public key** installed for the
  login user (`root` or `ubuntu`; both have been seen depending on the image).
- DNS: point the target hostname (e.g. `dev2.untrobotics.com`) at the server IP.
- The site repo (this repo). Scripts assume it is cloned to
  `/opt/untrobotics/website` on the server.

## 1. First connection

The host key changes on reinstall — clear the old one locally, then connect:

```sh
ssh-keygen -R dev2.untrobotics.com
ssh -o StrictHostKeyChecking=accept-new <root|ubuntu>@dev2.untrobotics.com
```

Quick sanity check: `lsb_release -a` (expect 24.04), `id`, `nproc`, `free -h`.

## 2. Clone the repo onto the server

```sh
sudo mkdir -p /opt/untrobotics
sudo chown "$USER" /opt/untrobotics
git clone -b chore/reconcile-prod-drift \
  https://github.com/untrobotics/website.git /opt/untrobotics/website
cd /opt/untrobotics/website
```

## 3. Install k3s + ingress-nginx

```sh
sudo bash deploy/install-k3s.sh
```

What it does: updates the OS, installs Docker (for building images) + ufw rules,
installs k3s with **Traefik disabled** (we use ingress-nginx so the WAF/rate-limit
annotations in `k8s/ingress.yaml` apply), waits for the node to be Ready, and
installs the ingress-nginx controller.

Verify:

```sh
export KUBECONFIG=/etc/rancher/k3s/k3s.yaml
kubectl get nodes
kubectl -n ingress-nginx get pods
```

## 4. Build images and load them into k3s

```sh
bash deploy/build-and-load-images.sh
```

Builds `untrobotics-web:dev` and `untrobotics-driver-ws:dev` and imports them
into k3s's containerd (no registry needed on a single node). The Deployments use
`imagePullPolicy: IfNotPresent`, so they use these local images.

## 5. Deploy the stack

```sh
sudo -E bash deploy/deploy.sh
```

Creates the `untrobotics` namespace, the `web-secrets` and `mysql-secrets`
Secrets (from `deploy/secrets.env`, or auto-generated dev defaults), the
`mysql-initdb` schema ConfigMap, then `kubectl apply -k k8s/`.

Verify:

```sh
kubectl -n untrobotics get pods,svc,ingress
kubectl -n untrobotics logs deploy/web
```

## 6. Reach the site

- The ingress routes `untrobotics.com` / `www.untrobotics.com`. For testing on
  the dev host, either point that DNS at the dev IP or send the Host header:
  ```sh
  curl -H 'Host: www.untrobotics.com' http://<server-ip>/
  ```
- The `.htaccess` skips the canonical/HTTPS redirect for `localhost`/`127.0.0.1`
  only, so test via the Host header above rather than the raw IP.

## 7. Real secrets (when needed)

Copy the template, fill values, re-run the deploy (or just the secret step):

```sh
cp k8s/secret.example.yaml deploy/secrets.env   # then convert to KEY=VALUE lines
# edit deploy/secrets.env  (gitignored)
sudo -E bash deploy/deploy.sh
```

---

## Pull from GHCR (CI-built images)

The `.github/workflows/build-images.yml` workflow builds both images on every
push to `develop`/`master` (and tags) and pushes them to:

- `ghcr.io/untrobotics/website` (app)
- `ghcr.io/untrobotics/website-driver-ws` (relay)

To deploy those instead of building on the node, skip
`build-and-load-images.sh` and use the GHCR overlay:

```sh
kubectl apply -k k8s/overlays/ghcr        # instead of `kubectl apply -k k8s/`
```

Pin the tag in `k8s/overlays/ghcr/kustomization.yaml` (a git sha or version) for
real deploys rather than `latest`.

**If the GHCR packages are private**, create a pull secret in the namespace and
attach it (or just set the packages to Public in GitHub since the site is public):

```sh
kubectl -n untrobotics create secret docker-registry ghcr-pull \
  --docker-server=ghcr.io \
  --docker-username=<github-user> \
  --docker-password=<a PAT with read:packages>
# then add `imagePullSecrets: [{name: ghcr-pull}]` to the pod specs (overlay patch).
```

## Email forwarder (self-hosted Postfix + SRS)

Registrar-agnostic forwarding of the human aliases (`hello@`, `webmaster@`, …) to
external mailboxes (Gmail). Inbound only — outbound transactional mail stays on
SendGrid. Lives in `mail/` (container) + `k8s/mail.yaml`. It is **not** applied by
`kubectl apply -k k8s/`; deploy it deliberately, since it owns the domain's MX.

Deploy:
```sh
# 1. Edit aliases (k8s/mail.yaml ConfigMap: alias -> destination mailbox).
# 2. Set a STABLE SRS secret:
kubectl -n untrobotics create secret generic mail-srs \
  --from-literal=SRS_SECRET="$(openssl rand -hex 32)" --dry-run=client -o yaml | kubectl apply -f -
# 3. Build + import the image:
docker build -t untrobotics-mail:dev mail/ && docker save untrobotics-mail:dev | sudo k3s ctr images import -
# 4. Open SMTP and deploy:
sudo ufw allow 25/tcp
kubectl apply -f k8s/mail.yaml
kubectl -n untrobotics logs deploy/mail
```

DNS records (anywhere you host DNS — registrar-agnostic):
- `A`   `mail.untrobotics.com` → server public IP
- `MX`  `untrobotics.com` → `10 mail.untrobotics.com`  (replaces name.com's forwarding MX)
- `PTR` (rDNS) server IP → `mail.untrobotics.com`  (set at the IP owner / **OVH panel** — critical for deliverability)
- `TXT` (SPF) `untrobotics.com`: `v=spf1 ip4:<server IP> include:sendgrid.net ~all`  (merge with existing SendGrid SPF)
- `TXT` (DMARC) `_dmarc.untrobotics.com`: `v=DMARC1; p=none; rua=mailto:postmaster@untrobotics.com`

Notes:
- Forwarding keeps the original sender's DKIM intact (body unchanged) so DMARC can
  pass on the sender's DKIM; SRS fixes the return-path SPF for bounces.
- Mount a real cert (cert-manager secret) at `/tls` for `mail.untrobotics.com` to
  replace the default self-signed STARTTLS cert (better deliverability).
- OVH: confirm outbound port 25 is enabled for the IP and set the rDNS in the panel.

Test BEFORE the MX cutover (send straight to the IP, bypassing MX):
```sh
swaks --server <server IP> --to hello@untrobotics.com --from you@example.com
# confirm arrival in the mapped Gmail; then flip MX. Validate at mail-tester.com.
```

### OrgSync auto-welcome ingest (replaces SendGrid Inbound Parse)

An OrgSync/CampusLabs join notification sent to `orgsync@untrobotics.com` is
received by this relay, piped to `mail/orgsync-ingest.py`, and POSTed to the
existing web handler `api/sendgrid-inbound/parse.php` (still the processor:
inserts into `orgsync_members`, emails the welcome + token). The `virtual` map
routes just that address to a local pipe (via the `orgsync-ingest.local`
pseudo-domain + `transport_maps` + the `orgsync-pipe` master.cf service) instead
of the Gmail catch-all. The handler is now gated by a shared secret.

1. Pick a secret and put it in BOTH namespaces (same value):
   ```sh
   INGEST=$(openssl rand -hex 32)
   # web handler side (prod web-secrets):
   kubectl -n untrobotics-prod patch secret web-secrets --type merge \
     -p "{\"stringData\":{\"INGEST_SECRET\":\"$INGEST\"}}"
   # mail pod side (same value, its own namespace):
   kubectl -n untrobotics-mail create secret generic mail-ingest \
     --from-literal=INGEST_SECRET="$INGEST" --dry-run=client -o yaml | kubectl apply -f -
   ```
   Then restart both: `kubectl -n untrobotics-prod rollout restart deploy/web`
   and `kubectl -n untrobotics-mail rollout restart deploy/mail`.
2. **OrgSync/CampusLabs reconfiguration:** point the club's join/roster
   notification (or a forward rule on whatever address currently receives them)
   at `orgsync@untrobotics.com`.
3. **DNS:** none needed — the `untrobotics.com` MX already points at this relay.
   You can now REMOVE the old SendGrid Inbound Parse: delete the parse-subdomain
   MX record (e.g. `MX parse.untrobotics.com -> mx.sendgrid.net`) and delete the
   Inbound Parse webhook host entry in the SendGrid dashboard.
4. **Test end-to-end:** send a crafted OrgSync-style HTML email (an
   `<a href="mailto:someone@example.com">Some Name</a>` in the HTML part) to
   `orgsync@untrobotics.com`; confirm `orgsync-ingest: OK: posted ...` in
   `kubectl -n untrobotics-mail logs deploy/mail`, a new row in `orgsync_members`,
   and the welcome email in the web/relay logs. A 403 in the mail log means the
   two INGEST_SECRET values don't match (mail defers + retries — nothing lost).

## Known gaps / TODO

- **PHP 7.2 -> 8.3:** prod runs 7.2; the image is 8.3. Expect compatibility fixes
  in the app on first boot (watch `kubectl -n untrobotics logs deploy/web`).
- **Sessions:** file-based; `web` is pinned to 1 replica. Move to Redis before
  scaling.
- **driver-ws is OFF by default** (`replicas: 0`) because the relay has no
  origin/auth checks. Enable only for an event:
  `kubectl -n untrobotics scale deploy/driver-ws --replicas=1`. Also note ingress
  handles 80/443 only, so exposing :81 externally needs a LoadBalancer Service on
  :81 or moving the firmware to wss on 443. Add auth before exposing it publicly.
- **PayPal SDK submodule:** path mismatch (`paypal/PP-BM-SDK` in code vs
  `paypal/paypal/PP-BM-SDK` in `.gitmodules`); payment button may be broken until
  resolved.
- **TLS:** handled for `dev2.untrobotics.com` via cert-manager + Let's Encrypt.
  Install with `sudo bash deploy/install-tls.sh` (after install-k3s.sh), then the
  ingress (`cert-manager.io/cluster-issuer: letsencrypt-prod` + tls block) auto-
  issues the cert via HTTP-01. Watch: `kubectl -n untrobotics get certificate`.
  For a different server, change the host in `k8s/ingress.yaml` (tls + rule) and
  the `.htaccess` redirect-exception. Prod hosts stay plain HTTP until migrated.
- **MySQL data:** only the schema is loaded in-cluster. Import real data from a
  prod dump separately (never the local test seed).

## Changelog (issues hit & resolved)

- **2026-06-27 — first deploy to dev2 (Ubuntu 26.04 LTS, k3s v1.36.2).** Stack came
  up: mysql-0 + web both Ready, homepage renders HTTP 200 on PHP 8.3 with MySQL
  connected. Issues found and fixed:
  - `ubuntu` account shipped with an admin-enforced expired password that blocked
    SSH sessions. `passwd` discards piped input (tcflush), so automate the forced
    change by feeding each line with a delay:
    `( sleep 4; echo OLD; sleep 3; echo NEW; sleep 3; echo NEW ) | ssh -tt ubuntu@host`.
  - `mysqli::init()` is deprecated in PHP 8.3 (template/top.php) — replaced with
    `parent::__construct()` (works on 7.2 and 8.3).
  - PHP deprecations were printing into pages — added `docker/php/zz-untrobotics.ini`
    (`display_errors=Off`, `log_errors=On`) to the image.
  - Behind the TLS-terminating ingress the pod always sees plain HTTP, so the
    `%{HTTPS} off` redirect would loop real HTTPS users — added
    `RewriteCond %{HTTP:X-Forwarded-Proto} !=https` to `.htaccess`.
  - To test a page without the HTTPS redirect, hit the pod with `Host: localhost`
    (the `.htaccess` localhost exception skips the redirect).
