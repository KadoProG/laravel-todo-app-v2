#!/bin/bash

# Laravelのストレージディレクトリの所有権とパーミッションを設定
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage

composer install

# PHP-FPMを起動
exec php-fpm
