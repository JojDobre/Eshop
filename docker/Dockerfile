# E-shop v Dockeri na lokálne skúšanie (PHP 8.3 + Apache). Databáza je samostatný kontajner (compose.yaml).
# Postup je rovnaký ako pri inštalačnom balíku: PHP knižnice a hotové štýly sú v obraze, nič sa nekompiluje pri spustení.

# 1. PHP knižnice (bez vývojových nástrojov). Skripty Composera sa spustia až v poslednom kroku s rozšíreniami PHP.
FROM composer:2 AS vendor
WORKDIR /app
COPY . .
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts --ignore-platform-reqs

# 2. Štýly a skripty šablón a administrácie.
FROM node:22-alpine AS assets
WORKDIR /app
COPY . .
COPY --from=vendor /app/vendor ./vendor
RUN npm ci --no-audit --no-fund && npm run build

# 3. Webový server s PHP.
FROM php:8.3-apache
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql intl gd zip bcmath exif opcache \
    && a2enmod rewrite headers \
    && sed -ri 's#/var/www/html#/var/www/html/public#g' /etc/apache2/sites-available/000-default.conf \
    && printf '<Directory /var/www/html/public>\n    AllowOverride All\n</Directory>\n' > /etc/apache2/conf-available/eshop.conf \
    && a2enconf eshop \
    && printf 'upload_max_filesize=64M\npost_max_size=64M\nmemory_limit=256M\nmax_execution_time=120\n' > /usr/local/etc/php/conf.d/eshop.ini

COPY --from=vendor /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=vendor /app/vendor ./vendor
COPY --chown=www-data:www-data --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && APP_KEY="base64:$(head -c 32 /dev/urandom | base64)" php artisan filament:assets --quiet \
    && rm -rf tests node_modules .env bootstrap/cache/*.php storage/logs/*.log \
    && find storage -name '*.json' -path '*eshop*' -delete \
    && mkdir -p storage/app/private storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache public/uploads \
    && chown -R www-data:www-data /var/www/html
