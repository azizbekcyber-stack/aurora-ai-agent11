#!/usr/bin/env sh
set -eu

php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan migrate --force
php artisan storage:link || true
php artisan optimize

php artisan queue:work --tries=1 --timeout=120 --sleep=2 &
QUEUE_PID=$!
trap 'kill "$QUEUE_PID" 2>/dev/null || true' INT TERM EXIT

php artisan serve --host=0.0.0.0 --port="${PORT:-8080}" --no-reload
