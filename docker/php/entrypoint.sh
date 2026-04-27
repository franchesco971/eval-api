#!/bin/sh
set -e

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

mkdir -p var/cache var/log public/images/covers
chown -R www-data:www-data var public/images

exec "$@"
