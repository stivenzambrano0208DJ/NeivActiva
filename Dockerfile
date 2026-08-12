# =============================================================================
# NeivActiva - Production Dockerfile (multi-stage)
# PHP 8.x MVC app (Composer, PhpSpreadsheet, PHPMailer) served by Apache.
# DocumentRoot = public/. Vendor deps are built here because vendor/ is gitignored.
# =============================================================================

# ---------------------------------------------------------------------------
# Stage 1 — Composer dependencies (no dev, optimized autoloader)
# ---------------------------------------------------------------------------
FROM composer:2 AS vendor

WORKDIR /app

# Only the manifests first, so this layer caches unless deps change.
COPY composer.json composer.lock ./

# --ignore-platform-reqs: the runtime image (stage 2) provides the PHP
# extensions; the composer image does not need them just to resolve/install.
RUN composer install \
        --no-dev \
        --optimize-autoloader \
        --no-interaction \
        --no-scripts \
        --prefer-dist \
        --ignore-platform-reqs

# ---------------------------------------------------------------------------
# Stage 2 — Runtime (Apache + PHP), slim, non-root
# ---------------------------------------------------------------------------
FROM php:8.3-apache

# --- System libs + PHP extensions required by the app ------------------------
# - pdo_mysql: database layer (app/Core/Database.php).
# - gd, zip, mbstring: required/recommended by phpoffice/phpspreadsheet.
#   (dom, xml, simplexml, xmlwriter are already bundled in this image.)
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libzip-dev \
        libonig-dev; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install -j"$(nproc)" \
        gd \
        zip \
        mbstring \
        pdo_mysql; \
    a2enmod rewrite; \
    apt-get purge -y --auto-remove; \
    rm -rf /var/lib/apt/lists/*

# --- PHP production config ----------------------------------------------------
# Base production ini + a drop-in that makes container env vars visible via
# $_ENV (the app reads $_ENV['DB_HOST'] etc.). Without variables_order=EGPCS,
# $_ENV would be empty in production and the DB config would fall back to
# localhost.
RUN set -eux; \
    mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"; \
    { \
        echo 'variables_order = "EGPCS"'; \
        echo 'expose_php = Off'; \
        echo 'upload_max_filesize = 16M'; \
        echo 'post_max_size = 20M'; \
    } > "$PHP_INI_DIR/conf.d/zz-neivactiva.ini"

# --- Apache: DocumentRoot -> public/, port 8080, non-root friendly ------------
RUN set -eux; \
    sed -i 's!Listen 80!Listen 8080!' /etc/apache2/ports.conf; \
    printf '%s\n' \
      'ServerName localhost' \
      'PidFile /tmp/apache2.pid' \
      '<VirtualHost *:8080>' \
      '    DocumentRoot /var/www/html/public' \
      '    <Directory /var/www/html/public>' \
      '        Options -Indexes +FollowSymLinks' \
      '        AllowOverride All' \
      '        Require all granted' \
      '    </Directory>' \
      '    ErrorLog /dev/stderr' \
      '    CustomLog /dev/stdout combined' \
      '</VirtualHost>' \
      > /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

# --- Application code (only what production needs; see .dockerignore) ---------
COPY --chown=www-data:www-data app/ ./app/
COPY --chown=www-data:www-data config/ ./config/
COPY --chown=www-data:www-data resources/ ./resources/
COPY --chown=www-data:www-data public/ ./public/

# --- Vendor dependencies from the build stage --------------------------------
COPY --chown=www-data:www-data --from=vendor /app/vendor/ ./vendor/

# --- Writable runtime dirs ----------------------------------------------------
# public/uploads receives user uploads (images, generated QR codes).
# NOTE: without a Dokploy volume these files are lost on each redeploy.
RUN set -eux; \
    mkdir -p /var/www/html/public/uploads; \
    chown -R www-data:www-data /var/www/html/public/uploads; \
    chown www-data:www-data /var/run/apache2 /var/lock

USER www-data

EXPOSE 8080

CMD ["apache2-foreground"]
