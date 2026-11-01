#!/bin/sh
set -eu
cd /var/www/html

# The runtime environment variables are set by Render, not by a .env file.
php artisan package:discover --ansi
php artisan config:cache

# Render Free doesn't provide a shell. Make schema migration opt-in via an env var.
# Laravel migrations are idempotent, but rerunning at every cold start adds latency.
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
  php artisan migrate --force
fi

php artisan view:cache
# Create the usual public/storage symlink if public uploads are used. The
# underlying Render Free filesystem is ephemeral: this does NOT persist files.
php artisan storage:link || true
chown -R www-data:www-data storage bootstrap/cache

exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/foundation.conf
