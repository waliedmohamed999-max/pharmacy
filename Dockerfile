FROM node:20-alpine AS node_build

WORKDIR /var/www/pharmacy

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build

FROM php:8.3-fpm-alpine AS php

WORKDIR /var/www/pharmacy

RUN apk add --no-cache \
    bash icu-dev libzip-dev oniguruma-dev mysql-client \
    freetype-dev libjpeg-turbo-dev libpng-dev \
    nginx gettext \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install intl mbstring pdo_mysql zip opcache gd

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts --optimize-autoloader

COPY . .
COPY --from=node_build /var/www/pharmacy/public/build ./public/build
RUN composer dump-autoload --optimize \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rw storage bootstrap/cache

COPY deploy/nginx/render.conf.template /etc/nginx/http.d/default.conf.template
COPY deploy/docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 10000

ENTRYPOINT ["/entrypoint.sh"]
