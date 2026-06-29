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

# --- Start postsrsd (forward socket 10001, reverse socket 10002) --------------
/usr/sbin/postsrsd -s /etc/postsrsd.secret -d "${SRS_DOMAIN}" -f 10001 -r 10002 &

# --- Postfix in the foreground (PID 1 of the container) -----------------------
# Recreate any missing queue directories + fix permissions. Needed when the mail
# spool is an empty (PVC-mounted) volume on first start.
postfix post-install create-missing >/dev/null 2>&1 || true
postfix set-permissions >/dev/null 2>&1 || true

postfix check || true
exec postfix start-fg
