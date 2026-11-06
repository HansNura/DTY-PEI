# PMT — Sistem Pelacakan DTY

Aplikasi pelacakan bobbin benang DTY dari produksi sampai pelanggan: batch produksi, timbang bobbin, packing kardus, QR code, surat jalan, validasi muatan, status perjalanan, bukti terima digital, laporan, dan audit.

Repositori ini dibangun bertahap mengikuti backlog Scrum 13 tiket (`DTY-001` s/d `DTY-013`). Sprint 1 (fondasi dan manajemen akses) sampai Sprint 5 (bukti terima digital dan rekap pengiriman) sudah selesai.

## Stack

- Laravel 12 (PHP 8.2+)
- Blade + Alpine.js + Tailwind CSS 4 (TALL ringan)
- SQLite untuk lokal; siap dipindah ke MySQL/PostgreSQL untuk produksi
- Vite 7
- Instrument Sans Variable (self-host) + design system Clean SaaS Light
- `qrcode` untuk membuat QR label secara lokal di browser
- `@zxing/browser` untuk scan QR kamera pada konfirmasi POD
- `leaflet` + OpenStreetMap untuk peta live tracking
- `barryvdh/laravel-dompdf` + `phpoffice/phpspreadsheet` untuk PDF/Excel
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
| Driver 2 | `driver2@pmt.test` | `password` |
| Driver 3 | `driver3@pmt.test` | `password` |
| Driver 4 | `driver4@pmt.test` | `password` |

Ubah kata sandi akun demo sebelum dipakai di lingkungan nyata.

### Alur produksi dan packing

1. **Produksi** — buat batch dengan merge dan spesifikasi, lalu aktifkan.
2. **Timbang bobbin** — masukkan berat; grade bobot, serial, dan QR dibuat otomatis.
3. **Packing kardus** — scan 6 bobbin dengan merge/grade sama; net, unit, grade, HU, dan tanggal dihitung otomatis, gross diisi dari timbangan fisik.
4. **Surat jalan** — logistik memilih bobbin yang sudah dipacking.
5. **Scan muatan → driver → POD** — alur pengiriman dan bukti terima seperti sebelumnya.

> Bobbin baru hanya lahir dari penimbangan batch aktif. Kardus baru hanya lahir dari packing; form input kardus/bobbin manual lama sudah dihapus.

### Anomali bobot bobbin

- Berat **> 5.600 g** → status **Overweight / Hold**, grade **OW**. Tidak boleh dipacking ke box reguler; label khusus OW tetap bisa dicetak.
- Berat **< 500 g** → status **Waste / Scrap**, grade **WASTE**. Label standar tidak dicetak; data tetap tersimpan untuk rekap limbah.
- Bobbin anomali ditolak oleh scanner packing dan tidak bisa masuk kardus pelanggan.
- Bobbin anomali tetap bisa diedit beratnya; jika kembali ke rentang standar, statusnya menjadi siap packing.

### Data demo produksi dan pelanggan

`ProductionBatchSeeder` mengisi 5 batch demo, termasuk `4D9164` sebagai batch aktif. `DtyInventorySeeder` mengisi 9 kardus dummy dan 66 bobbin dengan variasi grade dan status. `CustomerSeeder` mengisi 6 pelanggan dummy (`CUST-0001` sampai `CUST-0006`), termasuk PT Demo Tekstil, PT Sinar Benang Nusantara, dan PT Karya Rajut Sentosa.

```bash
php artisan db:seed --class=CustomerSeeder
```

### Data demo armada

`FleetSeeder` mengisi 6 armada dummy (`ARM-0001` sampai `ARM-0006`) dengan jenis kendaraan, kapasitas, dan pengemudi. Seeder ini juga memastikan akun driver demo tersedia.

```bash
php artisan db:seed --class=FleetSeeder
```

## Perintah berguna

```bash
php artisan migrate:fresh --seed   # reset database + batch, kardus, bobbin, pelanggan & armada demo
php artisan test                   # seluruh test
composer ci                        # Pint check + seluruh test
composer lint                      # perbaiki format kode otomatis
bash .ai/scripts/run-checks.sh     # Pint + test + build asset
bash .ai/scripts/db-reset.sh       # reset database lokal + seed
php artisan route:list             # daftar route
npm run dev                        # Vite dev server
composer run dev                   # server + queue + log + Vite
```

## Fitur

- Autentikasi session dengan throttle login.
- Empat peran: `admin`, `admin_gudang`, `logistik`, `driver`.
- Middleware `role` untuk membatasi route dan `EnsureUserIsActive` untuk menolak akun nonaktif.
- Manajemen pengguna (khusus admin): daftar dengan pencarian/filter, tambah, edit, aktif/nonaktif.
- Profil: ubah nama, email, dan kata sandi.
- Dashboard ringkas per peran.
- Security header dasar pada semua respons.
- Admin Gudang mengelola batch produksi (merge/spesifikasi), menimbang bobbin, dan melakukan packing kardus dengan scan 6 bobbin. Net weight, unit, grade, HU, dan tanggal kardus dihitung otomatis; label bobbin dan kardus dapat dicetak.
- Tim Logistik dapat mengelola pelanggan dengan kode internal, email, NPWP, alamat lengkap (kota/provinsi/kode pos), kontak penerima, dan titik lokasi yang dipilih langsung dari peta Leaflet.
- Tim Logistik dapat menerbitkan Surat Jalan dengan alokasi rol, mencetaknya, dan membatalkannya sebelum armada berangkat.
- Checker gudang dapat memindai QR rol (kamera, scanner USB, atau input manual) untuk memvalidasi muatan; salah DO, duplikat, dan kode tak dikenal ditolak.
- Validasi kapasitas armada terhadap total berat rol saat penerbitan DO.
- Driver memiliki halaman mobile-first untuk melihat perjalanan dan memperbarui status Berangkat, Dalam Perjalanan, dan Tiba beserta koordinat GPS.
- Tim Logistik memiliki dashboard live tracking berisi peta posisi terakhir, status armada, dan riwayat perjalanan per DO.
- Driver dapat memindai ulang QR seluruh rol, mencatat nama penerima, dan mengambil tanda tangan digital pelanggan sebagai POD.
- Tim Logistik dapat mencetak/mengunduh DO sebagai PDF, serta memfilter dan mengunduh rekap pengiriman per periode, lot, dan armada dalam PDF/Excel.
- Gulungan dibatalkan dan pelanggan/armada dinonaktifkan tanpa menghapus riwayat data.

## UI dan design system

- Arah visual **Clean SaaS Light**: kanvas terang, kartu putih, border tipis, aksen teal, font Instrument Sans self-host.
- App shell: sidebar di desktop dan bottom navigation di mobile, dengan menu berbeda per peran.
- Komponen Blade reusable di `resources/views/components/` (button, card, stat, badge, table, form, modal, timeline, stepper).
- Token warna, tipografi, dan bayangan ada di `resources/css/app.css`.
- Aturan UI lengkap: [`.ai/rules/ui-tailwind.md`](.ai/rules/ui-tailwind.md).

## Kontribusi tim

Alur branch, commit, pull/push, review, konflik, dan serah terima sprint ada di [`CONTRIBUTING.md`](CONTRIBUTING.md). Wajib dibaca Salman, Umar, dan kontributor berikutnya sebelum push pertama.

## Dokumentasi

- [`CONTRIBUTING.md`](CONTRIBUTING.md) — panduan kolaborasi GitHub dan serah terima sprint.
- [`docs/ERD.md`](docs/ERD.md) — rancangan skema data lintas sprint.
- [`docs/sprint-1.md`](docs/sprint-1.md) — laporan, kriteria penerimaan, dan bukti uji Sprint 1.
- [`docs/sprint-2.md`](docs/sprint-2.md) — master data, QR, pelanggan, dan armada.
- [`docs/sprint-3.md`](docs/sprint-3.md) — surat jalan, scanner, dan hasil perbaikan review.
- [`docs/sprint-4.md`](docs/sprint-4.md) — driver interface dan live tracking.
- [`docs/sprint-5.md`](docs/sprint-5.md) — POD dan rekap pengiriman PDF/Excel.
- [`Rencana Kerja Tim PMT.xlsx`](Rencana%20Kerja%20Tim%20PMT.xlsx) — backlog dan target sprint.
- [`AGENTS.md`](AGENTS.md) — panduan kerja seluruh AI agent.
- [`.ai/rules/`](.ai/rules/) — aturan arsitektur, database, dan UI.
- [`.ai/scripts/`](.ai/scripts/) — skrip verifikasi dan reset database.

## Status backlog

| Sprint | Tiket | Status |
| --- | --- | --- |
| 1 | DTY-001 Auth & User | Selesai |
| 2 | DTY-002 s/d DTY-005 | Selesai |
| 3 | DTY-006 s/d DTY-007 | Selesai |
| 4 | DTY-008 s/d DTY-009 | Selesai |
| 5 | DTY-010 s/d DTY-011 | Selesai |
| 6 | DTY-012 s/d DTY-013 | Selesai |
