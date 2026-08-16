#!/bin/sh
set -e

# 設定・ルート・ビューのキャッシュを起動時に作る。
# ビルド時に作らないのは、環境変数が実行時にしか確定しないため。
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
