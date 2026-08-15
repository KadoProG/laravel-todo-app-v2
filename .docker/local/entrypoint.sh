#!/bin/bash

# Laravelのストレージディレクトリの所有権とパーミッションを設定
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage

composer install

# docker compose run で渡したコマンドを実行（例: php artisan test --coverage）
if [ "$#" -gt 0 ]; then
    exec "$@"
fi

# PHP-FPMを起動
exec php-fpm
