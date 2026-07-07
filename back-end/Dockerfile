FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
        git \
        unzip \
        libzip-dev \
        libonig-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring zip fileinfo \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --optimize

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["docker-entrypoint.sh"]

# `php artisan serve` deliberately strips non-allowlisted env vars from the
# server process it spawns (so it re-reads .env on every request for its
# auto-restart-on-change feature). That makes it silently ignore container
# environment variables like DB_HOST, so use PHP's built-in server directly
# with Laravel's own router script instead.
CMD ["sh", "-c", "cd public && exec php -S 0.0.0.0:8000 -t . ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php"]
