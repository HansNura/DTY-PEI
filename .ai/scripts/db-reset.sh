#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

if [ -f .env ]; then
    APP_ENV_VALUE="$(sed -n 's/^APP_ENV=//p' .env | head -1 | tr -d '"[:space:]')"
else
    APP_ENV_VALUE=""
fi

APP_ENV_VALUE="${APP_ENV:-$APP_ENV_VALUE}"

if [ "$APP_ENV_VALUE" != "local" ] && [ "${ALLOW_DB_RESET:-0}" != "1" ]; then
    echo "Reset dibatalkan: APP_ENV=${APP_ENV_VALUE:-tidak diketahui}." >&2
    echo "Skrip ini hanya untuk database lokal. Setel ALLOW_DB_RESET=1 jika benar-benar yakin." >&2
    exit 1
fi

echo "==> Menghapus seluruh tabel dan menjalankan migrasi"
php artisan migrate:fresh --force

echo "==> Mengisi data awal"
php artisan db:seed --force

echo
echo "Database lokal siap. Akun demo (kata sandi: password):"
echo "  admin@pmt.test      Administrator"
echo "  gudang@pmt.test     Admin Gudang"
echo "  logistik@pmt.test   Tim Logistik"
echo "  driver@pmt.test     Driver"
