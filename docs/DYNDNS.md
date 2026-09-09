# Dynamic DNS service

A small service, built into the website, that lets members point a
`*.dyndns.untrobotics.com` sub-domain at a home/lab IP that changes over time —
the same idea as No-IP or DuckDNS, but on our own domain. A member's client
(router, cron job, or a ddclient-style script) calls an endpoint whenever its IP
changes, and we upsert the matching DNS record in the club's Name.com zone.

The code lives in `dyndns/` and ships inside the website container, so it runs
wherever the site runs. **It only functions in production** — the dev
environment has no Name.com credentials by design, so it never touches real DNS.

---

## For members

### 1. Get a key

Ask an admin for a dyndns key. Admins issue one from **Admin → Dynamic DNS**
(`/admin/dyndns`); a key can be left unrestricted or locked to specific
sub-domains. The admin hands you a ready-made update URL.

### 2. Pick a name

Your record is `<label>.dyndns.untrobotics.com`. You choose `<label>` (e.g.
`alice`), and in the request the **`sub_domain` value is `<label>.dyndns`** —
it must end in `.dyndns`. So `alice` becomes `alice.dyndns.untrobotics.com`.

### 3. Call the endpoint whenever your IP changes

```
GET https://untrobotics.com/dyndns/api/ip2host.php
      ?API_KEY=<your key>
      &super_domain=untrobotics.com
      &sub_domain=<label>.dyndns
      [&ip=<explicit IP>]      # optional; omitted = the caller's own IP
      [&ttl=<seconds>]         # optional; default 300
```

If `ip` is omitted the server uses the address the request came from — which is
usually exactly what you want from a home connection. IPv4 creates an `A`
record, IPv6 an `AAAA`. Each successful call replaces the previous record for
that name.

The response is a small JSON object:

```json
{"response":"IP updated successfully.","code":200}
```

### Examples

Cron every 5 minutes (uses the caller's IP):

```sh
*/5 * * * * curl -fsS "https://untrobotics.com/dyndns/api/ip2host.php?API_KEY=KEY&super_domain=untrobotics.com&sub_domain=alice.dyndns" >/dev/null
```

Explicit IP (e.g. from a script that already knows it):

```sh
curl "https://untrobotics.com/dyndns/api/ip2host.php?API_KEY=KEY&super_domain=untrobotics.com&sub_domain=alice.dyndns&ip=203.0.113.10&ttl=300"
```

Many consumer routers have a "custom / dynamic DNS" section — point it at the
same URL. A helper endpoint, `GET /dyndns/api/ip.php`, just echoes your current
public IP.

### Responses you might see

| Message | Meaning |
|---|---|
| `IP updated successfully.` | Record created/updated. |
| `Invalid API key.` | Key is wrong, or the key isn't allowed to set that sub_domain. |
| `You must specify a super domain.` | `super_domain` param missing. |
| `You are not allowed to modify this subdomain.` | `sub_domain` doesn't end in `.dyndns`. |
| `You are not allowed to modify this superdomain.` | `super_domain` isn't an allowed domain. |
| `Invalid IP address.` | The `ip` you passed isn't a valid IP. |
| `You probably don't own the domain.` (`code` 403) | Server-side: the Name.com credentials can't manage the domain (this is what dev always returns). |

---

## For admins

**Admin → Dynamic DNS** (`/admin/dyndns`) manages the service:

- **Issue a key** — optional description, and optional sub_domain restrictions
  (comma/space separated; blank = unrestricted). The key is generated for you;
  copy the update URL shown once on creation.
- **Revoke a key** — delete it; any client still using it stops updating.
- **Live records** — see every current `*.dyndns.untrobotics.com` record and
  delete stale ones. (Deletion is restricted to the dyndns suffix, so the page
  can never remove an unrelated zone record.) This panel is populated from
  Name.com, so it only shows data in production.

---

## How it works (maintainers)

- **Endpoint:** `dyndns/api/ip2host.php`. It (1) authenticates the caller's
  `API_KEY` against the `dyndns_api_keys` table, honouring any
  `subdomain_restrictions`; (2) enforces that `sub_domain` ends in
  `DYNDNS_FORCE_SUBDOMAIN` and `super_domain` is in
  `DYNDNS_ALLOWED_SUPERDOMAINS`; (3) validates the IP; then (4) via the Name.com
  client, confirms the domain is manageable, deletes any existing records for
  that host, and creates the new `A`/`AAAA` record.
- **Keys:** table `dyndns_api_keys` (`api_key_value`, `description`,
  `subdomain_restrictions`). `subdomain_restrictions` is a PHP-`serialize()`d
  array of allowed `sub_domain` values, or `NULL`/empty for unrestricted.
- **Name.com client:** `dyndns/api/namecom.php` is a small **v4 REST** client
  (HTTP Basic auth, no session). Name.com retired the old **v1** session API
  (`api.name.com/api` → HTTP 410), which this service originally used; the v4
  port restored it (URW-28). The same auth backs the maintainer DNS tool at
  `~/.claude/tools/namecom_dns.py`.
- **Config:** `DYNDNS_ALLOWED_SUPERDOMAINS` (default `['untrobotics.com']`) and
  `DYNDNS_FORCE_SUBDOMAIN` (`'dyndns'`) in `template/config*.php`. Credentials
  come from the environment — `NAMECOM_API_USERNAME` and `NAMECOM_API_KEY` (a
  v4 API token). **Prod has them set; dev does not**, which is why dev returns
  `403` on the actual DNS write.

### Troubleshooting

- **Every update returns `403 / don't own the domain`:** the environment's
  Name.com token is missing or invalid. Confirm `NAMECOM_API_KEY` is a valid v4
  token for an account that manages the domain.
- **`Invalid API key.` for a name that should work:** the key is restricted and
  the requested `sub_domain` isn't in its `subdomain_restrictions`.
- **Record not updating but no error:** confirm the client is actually calling
  the URL (check the record via Admin → Dynamic DNS → Live records).
