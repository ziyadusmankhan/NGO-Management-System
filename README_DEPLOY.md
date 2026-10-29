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
]) : [],

Keep the existing application's MySQL options code, including its PHP-version-compatible SSL attribute handling.

Create a Render Web Service using Docker, plan Free. Default port is 10000. The container includes Apache on port 10000. No build or start command is needed.

## Important
- PHP 8.4 is included, with mbstring and the application's database/document extensions. The image must provide PHP 8.4.1 or newer to satisfy the committed lockfile.
- Vite frontend is built if package.json exists and has `build` script.
- If there is an `artisan` process requiring database during build, move it to start.sh.
- Render Free storage is ephemeral. Store uploaded attachments externally; never use this for real NGO production data.
- Aiven database is separate and persistent, within Free plan limitations.
- For Render Free, migrations are triggered via RUN_MIGRATIONS=true at service startup because shell access is unavailable. Once schema is current, set it false to reduce cold starts.
- Don't run seeders with sensitive/production data on the free demo deployment.
- Apache serves only `/var/www/html/public`, not the Laravel project root.
