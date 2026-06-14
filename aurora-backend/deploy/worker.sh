#!/usr/bin/env sh
set -eu

php artisan config:clear
php artisan queue:work --tries=1 --timeout=120 --sleep=2
