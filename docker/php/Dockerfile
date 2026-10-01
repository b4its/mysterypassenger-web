# syntax=docker/dockerfile:1

FROM php:8.4-fpm-bookworm AS base

ARG UID=1000
ARG GID=1000
ARG NODE_MAJOR=22

ENV DEBIAN_FRONTEND=noninteractive \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_MEMORY_LIMIT=-1 \
    HOME=/home/www-data \
    COMPOSER_HOME=/home/www-data/.composer \
    npm_config_cache=/home/www-data/.npm

# ── Dependensi sistem ────────────────────────────────────────────────────────
RUN apt-get update && apt-get install -y --no-install-recommends \
        ca-certificates curl gnupg git unzip zip \
        libicu-dev libonig-dev libzip-dev libxml2-dev \
        libpng-dev libjpeg62-turbo-dev libfreetype6-dev libwebp-dev \
        default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# ── Ekstensi PHP ─────────────────────────────────────────────────────────────
RUN docker-php-ext-configure gd --with-jpeg --with-freetype --with-webp \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath exif gd intl mbstring opcache pcntl pdo_mysql zip \
    && pecl install redis pcov \
    && docker-php-ext-enable redis pcov

# ── Composer ─────────────────────────────────────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ── Node.js 22 LTS (sesuai permintaan: Node di service php) ──────────────────
RUN curl -fsSL https://deb.nodesource.com/setup_${NODE_MAJOR}.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && npm install -g npm@latest \
    && rm -rf /var/lib/apt/lists/*

# ── User non-root yang UID-nya cocok dengan host (hindari file root-owned) ───
RUN groupmod -o -g "${GID}" www-data \
    && usermod  -o -u "${UID}" -g "${GID}" www-data \
    && mkdir -p /var/www/html /home/www-data/.composer /home/www-data/.npm \
    && chown -R www-data:www-data /var/www/html /home/www-data

COPY docker/php/php.ini        /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/www.conf       /usr/local/etc/php-fpm.d/zz-www.conf
COPY docker/php/entrypoint.sh  /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

WORKDIR /var/www/html
USER www-data

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm", "-F"]
