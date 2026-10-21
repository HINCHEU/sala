# syntax=docker/dockerfile:1

# 1. PHP dependencies (no dev packages in the image).
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --no-dev --no-scripts

# 2. Front-end build. Tailwind scans vendor's pagination views, so vendor comes along.
FROM node:24-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

# 3. PHP-FPM runtime: the app and the worker (Horizon + scheduler) both run this image.
FROM php:8.3-fpm-alpine AS app
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
# Composer stays in the image so the dev container can run `composer setup` against the mounted source.
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
RUN install-php-extensions pdo_mysql redis pcntl gd zip intl bcmath exif opcache \
    && apk add --no-cache supervisor mysql-client \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php/opcache.ini $PHP_INI_DIR/conf.d/opcache.ini
COPY docker/php/app.ini $PHP_INI_DIR/conf.d/app.ini
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
WORKDIR /var/www/html
COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=vendor /app/vendor ./vendor
COPY --chown=www-data:www-data --from=assets /app/public/build ./public/build
RUN php artisan package:discover --ansi && chmod +x /usr/local/bin/entrypoint
ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]

# 4. Nginx with the built public files baked in.
FROM nginx:1.27-alpine AS web
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/html/public /var/www/html/public
