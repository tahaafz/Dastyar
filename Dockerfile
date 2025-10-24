# Stage 1
ARG PHP_VERSION=8.3
ARG COMPOSER_VERSION=2.8
ARG NODE_VERSION=24

FROM composer:${COMPOSER_VERSION} AS composer-builder
WORKDIR /var/www
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist

#Stage 2
FROM node:${NODE_VERSION}-alpine AS npm-builder
WORKDIR /var/www
COPY package*.json ./
COPY resources ./resources
COPY vite.config.js ./
RUN npm install && npm run build

#Stage 3
FROM php:${PHP_VERSION}-cli AS builder
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip curl libzip-dev libpng-dev libjpeg-dev libfreetype6-dev libonig-dev libxml2-dev libicu-dev libpq-dev libssl-dev \
    && docker-php-ext-install -j$(nproc) bcmath intl pdo_mysql zip pcntl sockets \
    && pecl install redis && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*
COPY --from=composer-builder /usr/bin/composer /usr/bin/composer
WORKDIR /var/www
COPY . /var/www/
COPY --from=composer-builder /var/www/vendor /var/www/vendor
COPY --from=npm-builder /var/www/public/build /var/www/public/build
RUN composer dump-autoload --optimize --no-interaction --no-ansi
RUN ./vendor/bin/rr get-binary
RUN php artisan octane:install --server=roadrunner --no-ansi --no-interaction

#Stage 4
FROM php:${PHP_VERSION}-cli AS production
RUN apt-get update && apt-get install -y --no-install-recommends supervisor \
    && apt-get clean && rm -rf /var/lib/apt/lists/*
COPY --from=builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=builder /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/
COPY --from=builder /usr/local/bin/docker-php-ext-* /usr/local/bin/
WORKDIR /var/www
COPY --from=builder /var/www /var/www
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
RUN mkdir -p /etc/supervisor/conf.d /var/log/supervisor
RUN echo '[program:horizon]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/artisan horizon
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/storage/logs/horizon.log
stopwaitsecs=3600
' > /etc/supervisor/conf.d/horizon.conf
RUN echo '[program:roadrunner]
process_name=%(program_name)s_%(process_num)02d
command=/var/www/rr serve -c /var/www/rr.yaml
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/storage/logs/roadrunner.log
stopwaitsecs=3600
' > /etc/supervisor/conf.d/roadrunner.conf
RUN chown -R www-data:www-data /var/www /var/www/storage
USER www-data
ENTRYPOINT ["/usr/bin/supervisord", "-c", "/etc/supervisor/supervisord.conf", "-n"]
EXPOSE 8000
