# The committed Symfony dependencies require PHP >= 8.4.1.
FROM php:8.4-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
      git unzip ca-certificates cron supervisor libzip-dev libicu-dev libonig-dev \
      libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring bcmath intl zip gd exif opcache \
    && a2enmod rewrite headers \
    && sed -ri 's/^Listen 80$/Listen 10000/' /etc/apache2/ports.conf \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY . .
COPY docker/apache.conf /etc/apache2/sites-available/laravel.conf
RUN a2dissite 000-default && a2ensite laravel \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader --no-scripts \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

COPY docker/start.sh /usr/local/bin/start-laravel
COPY docker/supervisord.conf /etc/supervisor/conf.d/foundation.conf
RUN printf '* * * * * www-data cd /var/www/html && /usr/local/bin/php artisan operations:scheduler-run >> storage/logs/scheduler.log 2>&1\n' > /etc/cron.d/foundation \
    && chmod 0644 /etc/cron.d/foundation
EXPOSE 10000
HEALTHCHECK --interval=60s --timeout=10s --start-period=180s --retries=3 \
    CMD php artisan operations:scheduler-health || exit 1
CMD ["sh", "/usr/local/bin/start-laravel"]
