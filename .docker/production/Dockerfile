# ---------- ステージ1: Composer 依存の解決 ----------
# 実行イメージと同じ PHP バージョンの上で composer を動かし、
# composer 公式イメージ側の PHP 差異で解決結果がぶれないようにする
FROM php:8.5-cli-bookworm AS vendor

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    libzip-dev \
    unzip \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./

# artisan がまだ無い状態で post-autoload-dump が走るのを防ぐ
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --no-progress \
    --no-scripts \
    --no-autoloader

COPY . .

RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

# ---------- ステージ2: 実行イメージ ----------
FROM php:8.5-fpm-bookworm

# nginx と supervisor を1コンテナに同居させ、ECS のタスク定義を単純に保つ
RUN apt-get update && apt-get install -y --no-install-recommends \
    libonig-dev \
    nginx \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

# opcache は PHP 8.5 のイメージに同梱済みのため install 対象に含めない。
# gd / exif / bcmath / zip は composer.lock のどのパッケージからも要求されておらず、
# アプリ側でも使っていないため含めない。
RUN docker-php-ext-install pdo_mysql mbstring pcntl

WORKDIR /var/www/html

COPY .docker/production/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY .docker/production/www.conf /usr/local/etc/php-fpm.d/zz-www.conf
COPY .docker/production/nginx.conf /etc/nginx/nginx.conf
COPY .docker/production/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY .docker/production/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

COPY --from=vendor /app /var/www/html

RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
