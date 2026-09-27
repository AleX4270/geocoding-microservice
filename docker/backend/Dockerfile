FROM php:8.4-apache AS base

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    && docker-php-ext-install \
    pdo_pgsql \
    bcmath \
    zip \
    pcntl

RUN a2enmod rewrite \
    && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf \
    && printf '%s\n' \
        '<Directory /var/www/html/public>' \
        '    Options -Indexes +FollowSymLinks' \
        '    AllowOverride All' \
        '    Require all granted' \
        '</Directory>' \
        > /etc/apache2/conf-available/symfony.conf \
    && a2enconf symfony

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

FROM base AS local

RUN pecl install pcov && docker-php-ext-enable pcov

COPY composer.json composer.lock ./

RUN composer install --no-interaction --no-scripts

FROM base AS production

COPY composer.json composer.lock ./

RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

COPY ./ ./

RUN composer dump-autoload --optimize
