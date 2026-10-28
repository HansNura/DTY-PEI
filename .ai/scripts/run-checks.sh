#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

if [ ! -x vendor/bin/pint ]; then
    echo "Dependensi Composer belum terpasang. Jalankan: composer install" >&2
    exit 1
fi

echo "==> Pint: pemeriksaan format"
vendor/bin/pint --test

echo "==> PHPUnit: seluruh test"
php artisan test

if [ "${SKIP_BUILD:-0}" = "1" ]; then
    echo "==> Build asset dilewati (SKIP_BUILD=1)"
else
    if [ ! -d node_modules ]; then
        echo "Dependensi Node belum terpasang. Jalankan: npm install --include=dev" >&2
        exit 1
    fi

    echo "==> Vite: build asset produksi"
    npm run build
fi

echo
echo "Semua pemeriksaan lulus."
