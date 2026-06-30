# Apple Pay domain association (placeholder)

`apple-developer-merchantid-domain-association` in this directory is a
**placeholder**. Apple Pay (shown automatically inside Stripe Checkout for
eligible devices/browsers) requires this domain to be registered with Stripe,
and the file to contain the exact contents Stripe gives you.

## How to enable Apple Pay

1. In the Stripe Dashboard go to:
   **Settings → Payments → Payment methods → Apple Pay → Add a new domain**
   and add `untrobotics.com` (and/or `dev.untrobotics.com` while testing).
2. Stripe will either auto-host the association file or give you the file
   contents to host yourself. If hosting yourself, replace the contents of
   `.well-known/apple-developer-merchantid-domain-association` with the exact
   text Stripe provides (no file extension, no trailing modifications).
3. Verify it is served at:
   `https://untrobotics.com/.well-known/apple-developer-merchantid-domain-association`
   It must return HTTP 200 with the raw file contents (Content-Type is not
   important; it must not 301/404 or be rewritten to `.php`).

## Routing note

The site's `.htaccess` extensionless rewrite only fires when a matching `.php`
file exists (`RewriteCond %{DOCUMENT_ROOT}/$1.php -f`), so this no-extension
file is served directly and is **not** rewritten. `.well-known` is not a
dotfile that Apache blocks by default (only `.ht*` is denied), so no extra
rule is required. If a future server config denies dotfile-prefixed paths, add
an explicit allow for `/.well-known/`.

Once Stripe verifies the domain, no code change is needed — Apple Pay appears
on the Checkout page created by `api/stripe/create-checkout-session.php`.
