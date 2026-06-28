# Self-hosted, registrar-agnostic email forwarder for untrobotics.com.
# Postfix (virtual alias forwarding) + postsrsd (SRS) — inbound only; outbound
# transactional mail still goes through SendGrid.
FROM debian:12-slim

# Preseed so the postfix package installs non-interactively; we ship our own main.cf.
RUN echo "postfix postfix/main_mailer_type string 'No configuration'" | debconf-set-selections \
    && apt-get update \
    && DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
        postfix \
        postsrsd \
        ca-certificates \
        openssl \
    && rm -rf /var/lib/apt/lists/*

COPY main.cf /etc/postfix/main.cf
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 25
ENTRYPOINT ["/entrypoint.sh"]
