#!/usr/bin/python3
"""
OrgSync/CampusLabs auto-welcome ingest — Postfix pipe transport.

Flow:
  A join-notification email addressed to  orgsync@untrobotics.com  arrives at the
  self-hosted Postfix relay. The `virtual` map rewrites that recipient to
  orgsync@orgsync-ingest.local (so it does NOT hit the Gmail catch-all), and
  transport_maps routes orgsync-ingest.local to the `orgsync-pipe` master.cf
  service, which runs THIS script (as the unprivileged `ingest` user) with the
  full raw message on stdin.

  We extract the text/html MIME part (decoding quoted-printable / base64 and the
  declared charset) and POST it as form field `html` to the existing web handler
  api/sendgrid-inbound/parse.php, authenticated with the shared X-Ingest-Secret.
  The handler stays the processor (regex-extracts the joiner, writes
  orgsync_members, emails the welcome + token).

Fail-safe:
  On ANY error we exit EX_TEMPFAIL (75) so Postfix DEFERS and retries instead of
  losing the mail. Only a 2xx from the handler yields exit 0.

Environment (exported to us by Postfix via import/export_environment, see the
mail entrypoint):
  INGEST_SECRET   shared secret (required; sent in X-Ingest-Secret)
  WEB_NS          web namespace (default untrobotics-prod) -> builds the URL
"""
import sys
import os
import subprocess
import email
from email import policy

EX_TEMPFAIL = 75  # sysexits.h — Postfix pipe(8) treats this as "defer + retry".
CURL = "/usr/bin/curl"


def fail(msg):
    """Log and defer (never lose the mail)."""
    sys.stderr.write("orgsync-ingest: DEFER: %s\n" % msg)
    sys.exit(EX_TEMPFAIL)


def extract_html(raw):
    """Return the decoded text/html body from a raw RFC822 message, or None."""
    try:
        msg = email.message_from_bytes(raw, policy=policy.default)
    except Exception as e:  # noqa: BLE001
        fail("could not parse MIME message: %r" % e)

    for part in msg.walk():
        if part.get_content_type() != "text/html":
            continue
        if (part.get_content_disposition() or "") == "attachment":
            continue
        try:
            return part.get_content()  # decodes CTE + charset for us
        except Exception:  # noqa: BLE001 — fall back to a lenient decode
            payload = part.get_payload(decode=True) or b""
            return payload.decode(part.get_content_charset() or "utf-8", "replace")
    return None


def main():
    raw = sys.stdin.buffer.read()
    if not raw:
        fail("empty message on stdin")

    html = extract_html(raw)
    if not html:
        # No HTML part -> the handler's mailto regex has nothing to work with.
        # Defer (not drop) so nothing is lost silently; a malformed/non-OrgSync
        # message will age out of the queue after maximal_queue_lifetime.
        fail("no text/html MIME part found in message")

    secret = os.environ.get("INGEST_SECRET", "")
    if not secret:
        fail("INGEST_SECRET not set in environment")

    web_ns = (os.environ.get("WEB_NS", "").strip() or "untrobotics-prod")
    url = "http://web.%s.svc.cluster.local/api/sendgrid-inbound/parse.php" % web_ns

    # POST form-encoded `html=<body>` via curl. --data-urlencode html@- reads the
    # value from stdin and url-encodes it (Content-Type application/x-www-form-
    # urlencoded), so PHP populates $_POST['html']. --fail-with-body makes curl
    # exit non-zero on any HTTP >= 400 (incl. a 403 secret mismatch) so we defer.
    try:
        proc = subprocess.run(
            [
                CURL,
                "--silent", "--show-error", "--fail-with-body",
                "--max-time", "30",
                "-H", "X-Ingest-Secret: %s" % secret,
                "--data-urlencode", "html@-",
                url,
            ],
            input=html.encode("utf-8"),
            capture_output=True,
        )
    except FileNotFoundError:
        fail("curl not found at %s" % CURL)

    if proc.returncode != 0:
        detail = (proc.stderr or proc.stdout).decode("utf-8", "replace")[:500]
        fail("POST to %s failed (curl exit %d): %s" % (url, proc.returncode, detail))

    sys.stderr.write(
        "orgsync-ingest: OK: posted %d bytes of html to %s\n" % (len(html), url)
    )
    sys.exit(0)


if __name__ == "__main__":
    main()
