# Production image for Railway (or any Docker host).
FROM php:8.3-cli

# PDO MySQL driver + unzip for Composer
RUN apt-get update \
 && apt-get install -y --no-install-recommends unzip \
 && docker-php-ext-install pdo_mysql \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

COPY . .

# Built-in server handles several requests in parallel with workers (Linux only).
ENV PHP_CLI_SERVER_WORKERS=4 \
    APP_ENV=production \
    APP_DEBUG=false

EXPOSE 8080
CMD ["sh", "-c", "php database/migrate.php && exec php -S 0.0.0.0:${PORT:-8080} -t public public/router.php"]
