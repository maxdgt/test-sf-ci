# syntax=docker/dockerfile:1

# ──────────────────────────────────────────────────────────────
# App image — PHP 8.4 FPM (Debian Bookworm). Fully supported by
# Symfony 7.4. Serves FastCGI on :9000; nginx sits in front of it.
# ──────────────────────────────────────────────────────────────
FROM php:8.4.3-fpm-bookworm AS base

# Robust extension installer (pulls the right system libs automatically).
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions \
        intl \
        opcache \
        zip \
        apcu

# Composer (build-time only).
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app

ENV APP_ENV=prod \
    APP_DEBUG=0 \
    COMPOSER_ALLOW_SUPERUSER=1

# ──────────────────────────────────────────────────────────────
# Builder — install prod dependencies and warm the prod cache.
# ──────────────────────────────────────────────────────────────
FROM base AS builder

# Cache the dependency layer on composer.lock alone.
COPY composer.json composer.lock symfony.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction \
        --no-progress

# Copy the source, build the optimized autoloader and warm the cache.
COPY . .
RUN composer dump-autoload --no-dev --classmap-authoritative \
    && composer run-script auto-scripts

# ──────────────────────────────────────────────────────────────
# Runtime — minimal FPM image running as the non-root www-data user.
# ──────────────────────────────────────────────────────────────
FROM base AS runtime

# Production php.ini + our opcache/preload tuning.
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php/conf.d/app.prod.ini "$PHP_INI_DIR/conf.d/app.prod.ini"

# Bring in the fully built application, owned by www-data.
COPY --from=builder --chown=www-data:www-data /app /app
RUN chown -R www-data:www-data /app/var

USER www-data

EXPOSE 9000
