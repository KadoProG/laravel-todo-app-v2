FROM php:8.5-fpm

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl

# opcache は PHP 8.5 のイメージに同梱済みのため install 対象に含めない
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

RUN pecl install pcov \
    && docker-php-ext-enable pcov

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY ./.docker/local/php.ini /usr/local/etc/php/

# docker-composeでマウントしているため不要
# COPY . /var/www

COPY ./.docker/local/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh



