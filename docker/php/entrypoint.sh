#!/bin/sh
set -e

cd /var/www/html

# Pastikan HOME menunjuk ke direktori yang bisa ditulis (cache Composer & npm).
export HOME="${HOME:-/home/www-data}"
export COMPOSER_HOME="${COMPOSER_HOME:-/home/www-data/.composer}"
export npm_config_cache="${npm_config_cache:-/home/www-data/.npm}"
mkdir -p "$COMPOSER_HOME" "$npm_config_cache" 2>/dev/null || true

# Siapkan .env pada run pertama
if [ ! -f .env ] && [ -f .env.example ]; then
    echo "[entrypoint] membuat .env dari .env.example"
    cp .env.example .env
fi

# Pastikan direktori yang dibutuhkan ada
mkdir -p \
    storage/app/private/survey-media \
    storage/app/public/report-assets \
    storage/app/private/exports \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    storage/fonts

# APP_KEY hanya dibuat jika kosong
if [ -f .env ] && ! grep -q '^APP_KEY=base64:' .env; then
    echo "[entrypoint] generate APP_KEY"
    php artisan key:generate --force --no-interaction || true
fi

# Tunggu MySQL siap (hanya bila service DB dikonfigurasi)
if [ -n "${DB_HOST}" ]; then
    echo "[entrypoint] menunggu ${DB_HOST}:${DB_PORT:-3306} ..."
    i=0
    until mysqladmin ping -h"${DB_HOST}" -P"${DB_PORT:-3306}" --silent 2>/dev/null; do
        i=$((i + 1))
        [ "$i" -ge 60 ] && { echo "[entrypoint] database tidak merespons, lanjut saja"; break; }
        sleep 1
    done
fi

exec "$@"
