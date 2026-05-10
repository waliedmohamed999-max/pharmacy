FROM php:8.3-fpm-alpine AS php

WORKDIR /var/www/pharmacy

RUN apk add --no-cache \
    bash icu-dev libzip-dev oniguruma-dev mysql-client \
    && docker-php-ext-install intl mbstring pdo_mysql zip opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts --optimize-autoloader

COPY . .
RUN composer dump-autoload --optimize \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rw storage bootstrap/cache

USER www-data

CMD ["php-fpm"]
