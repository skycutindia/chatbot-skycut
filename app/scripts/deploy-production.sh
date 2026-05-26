#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."

PHP="php"
[[ -f php-cli.ini ]] && PHP="php -c php-cli.ini"

echo "==> Composer (production)"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Migrate"
$PHP artisan migrate --force

echo "==> NPM build"
npm ci
npm run build

echo "==> Laravel optimize"
$PHP artisan storage:link 2>/dev/null || true
$PHP artisan config:cache
$PHP artisan route:cache
$PHP artisan view:cache
$PHP artisan platform:doctor

echo "Deploy prep complete."
