#!/bin/sh
# Render runtime config and start postsrsd + Postfix in the foreground.
set -e

MAIL_HOSTNAME="${MAIL_HOSTNAME:-mail.untrobotics.com}"
SRS_DOMAIN="${SRS_DOMAIN:-untrobotics.com}"

# --- SRS signing secret -------------------------------------------------------
# Must be STABLE across restarts, otherwise previously-issued SRS bounce
# addresses stop verifying. Provide it via the SRS_SECRET env (a k8s Secret);
# only fall back to generating one if none is supplied.
if [ -n "$SRS_SECRET" ]; then
    printf '%s' "$SRS_SECRET" > /etc/postsrsd.secret
elif [ ! -s /etc/postsrsd.secret ]; then
    echo "WARNING: no SRS_SECRET provided; generating an ephemeral one" >&2
    head -c 32 /dev/urandom | od -An -tx1 | tr -d ' \n' > /etc/postsrsd.secret
fi
chmod 600 /etc/postsrsd.secret

# --- Virtual alias map (mounted ConfigMap at /config/virtual) ------------------
if [ -f /config/virtual ]; then
    cp /config/virtual /etc/postfix/virtual
fi
touch /etc/postfix/virtual
postmap /etc/postfix/virtual

# --- OrgSync/CampusLabs auto-welcome ingest -----------------------------------
# orgsync@untrobotics.com is rewritten by the virtual map to
# orgsync@orgsync-ingest.local; transport_maps (below) routes that pseudo-domain
# to the `orgsync-pipe` service, which pipes the message into orgsync-ingest.py.
touch /etc/postfix/transport
postmap /etc/postfix/transport

# Register the pipe delivery service (idempotent). Runs as the unprivileged
# `ingest` user; Postfix hands the full raw message to the script on stdin.
postconf -M -e 'orgsync-pipe/unix=orgsync-pipe unix - n n - - pipe flags=Rq user=ingest argv=/usr/bin/python3 /usr/local/bin/orgsync-ingest.py'

# Postfix scrubs the environment of piped commands, so INGEST_SECRET / WEB_NS
# from the pod env would be invisible to the script. Import them into the master
# process and export them to the pipe child (keep the compiled-in defaults +
# PATH so curl/python resolve).
postconf -e 'import_environment=MAIL_CONFIG MAIL_DEBUG MAIL_LOGTAG TZ XAUTHORITY DISPLAY LANG=C POSTLOG_SERVICE POSTLOG_HOSTNAME PATH INGEST_SECRET WEB_NS'
postconf -e 'export_environment=TZ MAIL_CONFIG LANG PATH INGEST_SECRET WEB_NS'

# --- TLS cert: use a mounted one if present, else self-signed (opportunistic) --
mkdir -p /etc/postfix/tls
if [ -f /tls/tls.crt ] && [ -f /tls/tls.key ]; then
    cp /tls/tls.crt /etc/postfix/tls/tls.crt
    cp /tls/tls.key /etc/postfix/tls/tls.key
elif [ ! -f /etc/postfix/tls/tls.crt ]; then
    openssl req -x509 -newkey rsa:2048 -nodes -days 3650 \
        -keyout /etc/postfix/tls/tls.key -out /etc/postfix/tls/tls.crt \
        -subj "/CN=${MAIL_HOSTNAME}" 2>/dev/null
fi
chmod 600 /etc/postfix/tls/tls.key

postconf -e "myhostname=${MAIL_HOSTNAME}"

# Disable chroot for all master.cf services: this minimal image has no
# /etc/resolv.conf (etc.) inside /var/spool/postfix, so chrooted delivery agents
# can't resolve recipient MX records and every message would defer.
postconf -F '*/*/chroot=n'

newaliases 2>/dev/null || true

# --- DKIM signing (OpenDKIM milter) -------------------------------------------
# The private key is mounted from the `mail-dkim` k8s Secret at
# /etc/dkim/mail.private. Only sign if it's present; otherwise start Postfix
# WITHOUT the milter so mail still flows (unsigned) instead of deferring.
mkdir -p /etc/dkim
DKIM_KEY=/etc/dkim/mail.private
if [ -s "$DKIM_KEY" ]; then
    echo "DKIM: key found at ${DKIM_KEY}; starting OpenDKIM (signing enabled)."
    # The secret mount is read-only + root-owned; OpenDKIM (RequireSafeKeys)
    # needs a key it owns at 600, so copy it to an opendkim-owned runtime path.
    # This path (/run/opendkim/mail.key) matches KeyFile in opendkim.conf.
    mkdir -p /run/opendkim
    chown opendkim:opendkim /run/opendkim
    install -o opendkim -g opendkim -m 600 "$DKIM_KEY" /run/opendkim/mail.key
    /usr/sbin/opendkim -x /etc/opendkim.conf &
else
    echo "WARNING: no DKIM key at ${DKIM_KEY}; outbound mail will NOT be signed." >&2
    # Disable the milter so Postfix doesn't defer waiting on a dead socket.
    postconf -e 'smtpd_milters=' 'non_smtpd_milters='
fi

# --- Start postsrsd (forward socket 10001, reverse socket 10002) --------------
/usr/sbin/postsrsd -s /etc/postsrsd.secret -d "${SRS_DOMAIN}" -f 10001 -r 10002 &

# --- Postfix in the foreground (PID 1 of the container) -----------------------
# Recreate any missing queue directories + fix permissions. Needed when the mail
# spool is an empty (PVC-mounted) volume on first start.
postfix post-install create-missing >/dev/null 2>&1 || true
postfix set-permissions >/dev/null 2>&1 || true

postfix check || true
exec postfix start-fg
