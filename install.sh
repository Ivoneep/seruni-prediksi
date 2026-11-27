#!/usr/bin/env bash
set -e
HERE="$(cd "$(dirname "$0")" && pwd)"
APP="${1:-$HERE/../seruni-app}"
composer create-project laravel/laravel "$APP"
for d in app data database public python resources routes storage; do cp -r "$HERE/$d" "$APP/"; done
cp "$HERE/README.md" "$APP/"
cd "$APP"
php artisan migrate --force
php artisan seruni:import
echo "Selesai. Jalankan: cd $APP && php artisan serve"
