# Alwaysdata deployment and startup

The current deployment uses account `ngosystem`, application path `/home/ngosystem/www/foundation`, website root `www/foundation/public`, PHP 8.4, database host `mysql-ngosystem.alwaysdata.net`, database `ngosystem_demo`, and database user `ngosystem`. Credentials and encryption keys belong only in the server's private application-root `.env`.

The SSH user's configured home directory may be the website's `public` directory. Use the absolute application path rather than assuming `~/www/foundation` resolves to it.

## Missing bootstrap cache directory or Apache routing

The repository retains `bootstrap/cache/.gitignore` and `storage/framework/cache/data/.gitignore` so a fresh checkout includes the necessary empty runtime directories. The public `.htaccess` uses Laravel 13's standard Apache front-controller rules, including authorization/XSRF headers and trailing-slash redirects. Its source is https://github.com/laravel/laravel/blob/13.x/public/.htaccess.

On the server, run:

```sh
cd /home/ngosystem/www/foundation
export PHP_VERSION=8.4
git pull --ff-only origin feature/complete-ui-redesign
mkdir -p bootstrap/cache storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs
chmod -R u+rwX bootstrap/cache storage
php artisan --version
```

Keep `.env` at `/home/ngosystem/www/foundation/.env`, outside `public`. When transferring an existing database, preserve its original APP_KEY and SECURITY_LOOKUP_KEY and transfer both `storage/app/private` and `storage/app/public`. Use the production database credentials, HTTPS application URL, `APP_DEBUG=false`, and secure session cookies. The exported database includes migration bookkeeping; do not reset it or generate replacement encryption keys.

After the private configuration and uploaded files are in place, run:

```sh
chmod 600 .env
php artisan config:clear
php artisan package:discover --ansi
php artisan migrate:status
php artisan view:cache
php artisan config:cache
php artisan security:health
php artisan audit:health
```

Review any pending migrations before applying them. Check the HTTPS login page, login with an existing account, protected document access, and exports. If CLI startup works but HTTP fails, inspect the Alwaysdata site's Apache/PHP error log as well as the private Laravel log. Do not enable public debug output or publish logs/credentials.

The scheduler and queue worker require separate Alwaysdata Scheduled Tasks/Services setup. Docker files in the repository target the optional Render deployment; Alwaysdata's PHP site runs the application directly.
