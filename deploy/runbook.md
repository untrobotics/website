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
- **TLS:** ingress TLS via cert-manager is commented in `k8s/ingress.yaml`;
  install cert-manager + a ClusterIssuer for real certs.
- **MySQL data:** only the schema is loaded in-cluster. Import real data from a
  prod dump separately (never the local test seed).

## Changelog (issues hit & resolved)

- _(to be filled in during the first real run)_
