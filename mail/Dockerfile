# Self-hosted, registrar-agnostic email forwarder for untrobotics.com.
# Postfix (virtual alias forwarding) + postsrsd (SRS) — inbound only; outbound
# transactional mail still goes through SendGrid.
FROM debian:12-slim

# Preseed so the postfix package installs non-interactively; we ship our own main.cf.
# opendkim + opendkim-tools provide the DKIM-signing milter (see opendkim.conf).
RUN echo "postfix postfix/main_mailer_type string 'No configuration'" | debconf-set-selections \
    && apt-get update \
    && DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
        postfix \
        postsrsd \
        opendkim \
        opendkim-tools \
        ca-certificates \
        openssl \
        curl \
        python3 \
    && rm -rf /var/lib/apt/lists/*

# Unprivileged user the OrgSync ingest pipe (master.cf `orgsync-pipe`) runs as.
RUN useradd --system --no-create-home --shell /usr/sbin/nologin ingest

COPY main.cf /etc/postfix/main.cf
COPY transport /etc/postfix/transport
COPY opendkim.conf /etc/opendkim.conf
COPY opendkim-trusted /etc/opendkim/TrustedHosts
# OrgSync/CampusLabs auto-welcome ingest: reads the raw email on stdin, extracts
# the text/html part, and POSTs it to the web handler (see the pipe transport
# wired up in entrypoint.sh).
COPY orgsync-ingest.py /usr/local/bin/orgsync-ingest.py
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh /usr/local/bin/orgsync-ingest.py

EXPOSE 25
ENTRYPOINT ["/entrypoint.sh"]
