# PMT — Sistem Pelacakan DTY

Aplikasi pelacakan rol benang DTY dari gudang sampai pelanggan: master data rol, QR code, surat jalan, validasi muatan, status perjalanan, bukti terima digital, laporan, dan audit.

Repositori ini dibangun bertahap mengikuti backlog Scrum 13 tiket (`DTY-001` s/d `DTY-013`). Sprint 1 (fondasi dan manajemen akses) sudah selesai.

## Stack

- Laravel 12 (PHP 8.2+)
- Blade + Alpine.js + Tailwind CSS 4 (TALL ringan)
- SQLite untuk lokal; siap dipindah ke MySQL/PostgreSQL untuk produksi
- Vite 7
- PHPUnit 11 + Laravel Pint

## Menjalankan di lokal

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm install --include=dev
npm run build
php artisan serve
```

Buka `http://localhost:8000`.

> Jika `NODE_ENV=production` aktif di shell, npm melewati `devDependencies`. Gunakan `npm install --include=dev` dan `npm run build`.

### Akun demo

Dibuat oleh `UserSeeder`:

| Peran | Email | Kata sandi |
| --- | --- | --- |
| Administrator | `admin@pmt.test` | `password` |
| Admin Gudang | `gudang@pmt.test` | `password` |
| Tim Logistik | `logistik@pmt.test` | `password` |
| Driver | `driver@pmt.test` | `password` |

Ubah kata sandi akun demo sebelum dipakai di lingkungan nyata.

## Perintah berguna

```bash
php artisan migrate:fresh --seed   # reset database + akun demo
php artisan test                   # seluruh test
composer ci                        # Pint check + seluruh test
composer lint                      # perbaiki format kode otomatis
bash .ai/scripts/run-checks.sh     # Pint + test + build asset
bash .ai/scripts/db-reset.sh       # reset database lokal + seed
php artisan route:list             # daftar route
npm run dev                        # Vite dev server
composer run dev                   # server + queue + log + Vite
```

## Struktur fitur Sprint 1

- Autentikasi session dengan throttle login.
- Empat peran: `admin`, `admin_gudang`, `logistik`, `driver`.
- Middleware `role` untuk membatasi route dan `EnsureUserIsActive` untuk menolak akun nonaktif.
- Manajemen pengguna (khusus admin): daftar dengan pencarian/filter, tambah, edit, aktif/nonaktif.
- Profil: ubah nama, email, dan kata sandi.
- Dashboard ringkas per peran.
- Security header dasar pada semua respons.

## Kontribusi tim

Alur branch, commit, pull/push, review, konflik, dan serah terima sprint ada di [`CONTRIBUTING.md`](CONTRIBUTING.md). Wajib dibaca Salman, Umar, dan kontributor berikutnya sebelum push pertama.

## Dokumentasi

- [`CONTRIBUTING.md`](CONTRIBUTING.md) — panduan kolaborasi GitHub dan serah terima sprint.
- [`docs/ERD.md`](docs/ERD.md) — rancangan skema data lintas sprint.
- [`docs/sprint-1.md`](docs/sprint-1.md) — laporan, kriteria penerimaan, dan bukti uji Sprint 1.
- [`Rencana Kerja Tim PMT.xlsx`](Rencana%20Kerja%20Tim%20PMT.xlsx) — backlog dan target sprint.
- [`AGENTS.md`](AGENTS.md) — panduan kerja seluruh AI agent.
- [`.ai/rules/`](.ai/rules/) — aturan arsitektur, database, dan UI.
- [`.ai/scripts/`](.ai/scripts/) — skrip verifikasi dan reset database.

## Status backlog

| Sprint | Tiket | Status |
| --- | --- | --- |
| 1 | DTY-001 Auth & User | Selesai |
| 2 | DTY-002 s/d DTY-005 | Belum dimulai |
| 3 | DTY-006 s/d DTY-007 | Belum dimulai |
| 4 | DTY-008 s/d DTY-009 | Belum dimulai |
| 5 | DTY-010 s/d DTY-011 | Belum dimulai |
| 6 | DTY-012 s/d DTY-013 | Belum dimulai |
