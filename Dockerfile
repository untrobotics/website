# UNT Robotics website — PHP/Apache application image.
#
# Production currently runs PHP 7.2 + Apache (mod_php). This image targets the
# modern PHP 8.3; expect some 7.2 -> 8.3 compatibility fixes to surface when the
# stack is exercised. Apache + mod_rewrite is kept so the existing .htaccess
# routing works unchanged (lift-and-shift).
FROM php:8.3-apache

# --- PHP extensions -----------------------------------------------------------
# Only what the codebase actually uses: mysqli/pdo_mysql (DB), gmp (Discord
# ed25519 verify via simplito/elliptic-php), bcmath, intl, zip, mbstring, exif,
# calendar, gettext, opcache. curl/openssl/sodium are already built into the
# official image. gd/imap/soap/mailparse are unused and intentionally omitted.
RUN apt-get update && apt-get install -y --no-install-recommends \
        libicu-dev \
        libgmp-dev \
        libzip-dev \
        libonig-dev \
        git \
        unzip \
    && docker-php-ext-install -j"$(nproc)" \
        mysqli \
        pdo_mysql \
        bcmath \
        gmp \
        intl \
        zip \
        mbstring \
        exif \
        calendar \
        gettext \
        opcache \
    && rm -rf /var/lib/apt/lists/*

# Composer (for the Discord interactions endpoint's ed25519 dependency).
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# --- Apache -------------------------------------------------------------------
# Enable rewrite + headers and allow .htaccess overrides under the docroot.
RUN a2enmod rewrite headers \
    && sed -ri 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf

WORKDIR /var/www/html
COPY . /var/www/html/

# Use the environment-driven config inside the container. The real (secret)
# template/config.php is excluded from the build context by .dockerignore.
RUN cp template/config.docker.php template/config.php

# Install the Discord endpoint's ed25519 library (declared in composer.json).
RUN composer install --no-dev --no-interaction --prefer-dist \
        --working-dir=api/discord/interactions

# Runtime-writable directories + ownership.
RUN mkdir -p paypal/logs api/rocketry/gps/locations cron \
    && chown -R www-data:www-data /var/www/html

EXPOSE 80
