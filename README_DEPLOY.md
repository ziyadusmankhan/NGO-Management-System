# Render + Aiven starter for an existing Laravel/MySQL project

These optional Docker files target Render, not Alwaysdata. The application source can be deployed separately to Alwaysdata. The committed dependencies require PHP 8.4.1 or newer; the Docker image uses PHP 8.4.

## Install
Copy `Dockerfile`, `.dockerignore`, `.gitattributes`, and `docker/` into the ROOT of your existing Laravel repository. Do not replace existing Laravel files.
Download the project CA certificate from Aiven Overview and save it as `docker/ca.pem` (not included). A CA certificate is public; never save a private key or DB password in your repository.

## Render environment
- APP_ENV=production
- APP_DEBUG=false
- APP_KEY=base64:... (preserve the existing application's key when transferring its encrypted data)
- SECURITY_LOOKUP_KEY=(preserve the existing application's lookup key when transferring existing data; configure as a secret)
- APP_URL=https://your-actual-render-url.onrender.com
- DB_CONNECTION=mysql
- DB_HOST=your-aiven-hostname
- DB_PORT=the-port-provided-by-aiven
- DB_DATABASE=defaultdb
- DB_USERNAME=avnadmin
- DB_PASSWORD=(secret; input on Render only)
- MYSQL_ATTR_SSL_CA=/var/www/html/docker/ca.pem
- LOG_CHANNEL=stderr
- RUN_MIGRATIONS=true (initial deployment; set to false afterwards)
- SESSION_DRIVER=database (only if your sessions migration exists)
- CACHE_STORE=database (only if cache tables exist, for modern Laravel)
- QUEUE_CONNECTION=sync (simple demo; actual workers are not running)

The project's config/database.php MySQL connection must include its typical SSL options array:
'options' => extension_loaded('pdo_mysql') ? array_filter([
    \PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
    \PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
]) : [],

Keep the existing application's MySQL options code, including its PHP-version-compatible SSL attribute handling.

Create a Render Web Service using Docker, plan Free. Default port is 10000. The container includes Apache on port 10000. No build or start command is needed.

## Important
- PHP 8.4 is included, with mbstring and the application's database/document extensions. The image must provide PHP 8.4.1 or newer to satisfy the committed lockfile.
- Public assets are committed and need no Node/Vite build. Node is used only for UI checks.
- If there is an `artisan` process requiring database during build, move it to start.sh.
- Render Free storage is ephemeral. Store uploaded attachments externally; never use this for real NGO production data.
- Aiven database is separate and persistent, within Free plan limitations.
- For Render Free, migrations are triggered via RUN_MIGRATIONS=true at service startup because shell access is unavailable. Once schema is current, set it false to reduce cold starts.
- Don't run seeders with sensitive/production data on the free demo deployment.
- Apache serves only `/var/www/html/public`, not the Laravel project root.

## Scheduler and health

The Docker image supervises Apache and cron. Every minute cron runs
`php artisan operations:scheduler-run` as www-data. It records a heartbeat
only after the due commands succeed. Check with:

```sh
php artisan operations:scheduler-health
```

A missing, failed or older-than-three-minute heartbeat exits nonzero. Inspect
`storage/logs/scheduler.log` and Laravel logs if it fails. Monitor this command
from your hosting health/monitoring service, separately from HTTP availability.
The Docker HEALTHCHECK runs it once per minute after a three-minute startup grace period.
Hosts that ignore Docker HEALTHCHECK still need an external monitor.
Do not also configure an external scheduler for the same Docker instance.

For Alwaysdata or another non-Docker installation, configure one scheduled task
every minute (PHP 8.4.1+):

```sh
cd /home/ngosystem/www/foundation && php artisan operations:scheduler-run
```

Use a persistent shared cache store. Host restarts/cache clearing remove the
heartbeat until the next successful minute. Reminder times are Asia/Karachi.
Laravel's scheduler itself does not send payments or supplier messages.

## Reproducible UI checks

```sh
composer install
npm ci
npx playwright install --with-deps chromium
UI_EXPORT_PREVIEWS=1 vendor/bin/phpunit --filter UiRedesignTest
npm run check:ui
```

PowerShell: set `$env:UI_EXPORT_PREVIEWS='1'`, then run
`php vendor/bin/phpunit --filter UiRedesignTest`. CI performs the same sequence.
The browser checks use synthetic test responses, not live accounts. Playwright
and its exact dependency tree are retained in package.json/package-lock.json.
The full test suite uses `php -d memory_limit=1G vendor/bin/phpunit` in CI.
Run suites sequentially because their fake evidence disks share a test directory.

## TLS verification

`MYSQL_ATTR_SSL_CA` is read by the actual MySQL connection configuration and
server certificate verification remains enabled. Mount the CA at that path,
use the certificate's hostname as DB_HOST, then rebuild the config cache.
Verify the deployed connection (without printing credentials):

```sh
php artisan tinker --execute='dump(DB::select("SHOW SESSION STATUS LIKE \"Ssl_cipher\""));'
```

The cipher must be non-empty. A local configuration test does not establish
that a remote production server negotiated TLS.
