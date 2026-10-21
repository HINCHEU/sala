#!/bin/sh
set -e

# Config and route caches read env at build time, so build them when the container starts, not in the image.
if [ "$APP_ENV" = "production" ] || [ "$APP_ENV" = "staging" ]; then
    php artisan optimize
fi

exec "$@"
