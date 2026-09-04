# syntax=docker/dockerfile:1

###############################################################################
# Backend - Laravel 12 API (PHP 8.4)
#
# Matches the lockfile: composer.lock was resolved with PHP 8.4 (the Symfony 8
# packages locked in it require php >= 8.4).
#
# The image serves the API with `php artisan serve` on port 8000.
# On every container start the entrypoint:
#   1. waits until MySQL accepts connections,
#   2. fixes storage/ permissions (the storage volume may be brand new),
#   3. creates the public/storage symlink,
#   4. generates APP_KEY on first boot (skip by passing APP_KEY via env),
#   5. runs pending migrations (web/serve containers only, not queue workers),
#   6. drops privileges to www-data and starts the container command.
###############################################################################

FROM php:8.4-cli-alpine

# ---- PHP extensions required by Laravel + Intervention/Image (GD) ----------
RUN apk add --no-cache \
        icu-libs libzip libpng libjpeg-turbo freetype libwebp su-exec \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS icu-dev libzip-dev libpng-dev libjpeg-turbo-dev freetype-dev libwebp-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql bcmath exif pcntl gd intl zip opcache \
    && apk del .build-deps \
    && docker-php-source delete

# ---- PHP configuration ------------------------------------------------------
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php.ini "$PHP_INI_DIR/conf.d/99-app.ini"

# ---- Composer ---------------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ---- Application code -------------------------------------------------------
# (.env is excluded via .dockerignore - configuration is injected at runtime
#  through docker-compose environment variables, with .env.example as fallback)
COPY . .
RUN sed -i 's/\r$//' docker/entrypoint.sh && chmod +x docker/entrypoint.sh \
    && if [ ! -f .env ]; then cp .env.example .env; fi \
    && composer install --no-interaction --prefer-dist --optimize-autoloader --no-progress \
    && chown -R www-data:www-data /var/www/html

# ---- Runtime -----------------------------------------------------------------
# artisan serve uses this to handle concurrent requests
ENV PHP_CLI_SERVER_WORKERS=4

EXPOSE 8000

HEALTHCHECK --interval=15s --timeout=5s --start-period=90s --retries=10 \
    CMD php -r 'exit(@fsockopen("127.0.0.1", 8000, $e, $m, 3) ? 0 : 1);'

ENTRYPOINT ["/var/www/html/docker/entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

