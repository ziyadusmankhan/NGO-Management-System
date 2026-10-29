# Optional frontend/Vite build. Laravel projects without package.json skip npm.
FROM node:22-bookworm-slim AS frontend
WORKDIR /src
COPY . .
RUN if [ -f package.json ]; then \
      if [ -f package-lock.json ]; then npm ci; else npm install; fi; \
      npm run build; \
    fi

# The committed Symfony dependencies require PHP >= 8.4.1.
FROM php:8.4-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
      git unzip ca-certificates libzip-dev libicu-dev libonig-dev \
      libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring bcmath intl zip gd exif opcache \
    && a2enmod rewrite headers \
    && sed -ri 's/^Listen 80$/Listen 10000/' /etc/apache2/ports.conf \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY . .
# Copy frontend-generated public/build files without replacing the Laravel app.
COPY --from=frontend /src/public/ /var/www/html/public/
COPY docker/apache.conf /etc/apache2/sites-available/laravel.conf
RUN a2dissite 000-default && a2ensite laravel \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader --no-scripts \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

COPY docker/start.sh /usr/local/bin/start-laravel
EXPOSE 10000
CMD ["sh", "/usr/local/bin/start-laravel"]
