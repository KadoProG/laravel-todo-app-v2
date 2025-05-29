FROM php:8.3-fpm

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd opcache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY ./.docker/php/php.ini /usr/local/etc/php/

# docker-composeでマウントしているため不要
# COPY . /var/www

COPY ./.docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh



