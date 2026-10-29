# Panduan AI Agent — Proyek PMT

Dokumen ini adalah sumber kebenaran tunggal untuk semua AI agent yang bekerja di repositori ini. Baca bagian yang relevan sebelum mengubah kode. Aturan rinci ada di `.ai/rules/`:

- `.ai/rules/architecture.md` — pola controller, service, dan konvensi kode.
- `.ai/rules/database-guidelines.md` — migrasi, indexing, dan kebijakan data.
- `.ai/rules/ui-tailwind.md` — standar Blade, Alpine.js, dan Tailwind.

## Ringkasan proyek

PMT adalah sistem pelacakan rol benang DTY dari gudang sampai pelanggan: master data rol, QR code, surat jalan (DO), validasi muatan, status perjalanan, bukti terima digital (POD), laporan, dan audit.

Backlog berasal dari `Rencana Kerja Tim PMT.xlsx` (13 tiket `DTY-001` s/d `DTY-013`, Sprint 1–6). Rancangan data lintas sprint ada di `docs/ERD.md`.

Status saat ini: **Sprint 1 dan Sprint 2 selesai** (auth/RBAC, master gulungan DTY dan QR, pelanggan, armada). Sprint 3 belum dimulai.

## Stack

- Laravel 12, PHP 8.2+ (lokal memakai PHP 8.4)
- Blade + Alpine.js + Tailwind CSS 4 + Vite 7
- SQLite untuk lokal; target produksi MySQL/PostgreSQL
- PHPUnit 11, Laravel Pint

## Perintah wajib

```bash
# pengembangan
php artisan serve
npm run dev
composer run dev

# verifikasi sebelum menyatakan selesai
bash .ai/scripts/run-checks.sh

# reset database lokal + seed akun demo
bash .ai/scripts/db-reset.sh
```

Perintah individual:

```bash
php artisan test
vendor/bin/pint --test
vendor/bin/pint            # perbaiki format
npm run build
php artisan route:list
```

## Peta direktori penting

```text
app/Enums/                  enum domain (UserRole)
app/Http/Controllers/       controller tipis
app/Http/Controllers/Admin/ area khusus admin
app/Http/Middleware/        role, EnsureUserIsActive, SecurityHeaders
app/Http/Requests/          validasi FormRequest
app/Models/                 model Eloquent
database/migrations/        skema
database/seeders/           data awal & akun demo
docs/                       ERD dan laporan sprint
resources/views/            Blade
resources/js/app.js         entry Alpine
resources/css/app.css       entry Tailwind
routes/web.php              route web
tests/Feature/              test perilaku
.ai/                        aturan & skrip agent
```

## Peran dan akses

| Peran (`users.role`) | Label | Fokus |
| --- | --- | --- |
| `admin` | Administrator | Akun, hak akses, audit |
| `admin_gudang` | Admin Gudang | Master DTY, QR, validasi muatan |
| `logistik` | Tim Logistik | Pelanggan, armada, DO, tracking, laporan |
| `driver` | Driver | Status perjalanan, POD |

Gunakan middleware `role:<peran>` untuk membatasi route. Jangan menaruh pengecekan peran langsung di Blade untuk otorisasi; Blade hanya untuk menyembunyikan UI.

## Glosarium domain

- **DTY**: draw textured yarn, benang yang dilacak.
- **Rol/gulungan**: unit fisik yang memiliki satu QR unik.
- **Lot**: nomor batch produksi.
- **DO**: delivery order / surat jalan.
- **Checker**: petugas gudang yang memindai muatan.
- **POD**: proof of delivery / bukti terima.
- **PEI**: lingkungan uji yang disebut pada tiket DTY-013.

## Aturan kerja agent

1. **Baca dulu, ubah kemudian.** Jangan mengubah file yang belum dibaca pada sesi berjalan.
2. **Ikuti pola yang ada.** Controller tipis, validasi di FormRequest, logika domain berulang di service.
3. **Jangan menambah komentar** kecuali diminta atau logika benar-benar tidak jelas.
4. **Jangan mengubah** `vendor/`, `node_modules/`, `public/build/`, `storage/`, `database/*.sqlite`, `.env`, atau `.commandcode/taste/`.
5. **Jangan membuat commit** kecuali diminta eksplisit.
6. **Perubahan skema** harus berupa migrasi baru; jangan mengedit migrasi yang sudah pernah dijalankan di lingkungan bersama.
7. **Data operasional bersifat historis.** Jangan hard-delete transaksi; gunakan status pembatalan.
8. **Bahasa UI dan pesan validasi**: Indonesia. Nama kelas, method, kolom kode: Inggris; istilah domain tetap Indonesia (`nama`, `nomor_lot`, `berat_gram`).
9. **Definisi selesai**: `bash .ai/scripts/run-checks.sh` lulus (Pint, seluruh test, build asset).
10. **Keamanan**: jangan menonaktifkan middleware, CSRF, atau throttle untuk mempercepat pekerjaan.

## Alur kerja tiket

1. Baca tiket terkait di spreadsheet dan `docs/ERD.md`.
2. Tulis migrasi/model/enum bila menyentuh data baru.
3. Implementasi route → FormRequest → controller → service (bila perlu) → view.
4. Tambah test fitur untuk jalur utama dan kasus otorisasi.
5. Jalankan `.ai/scripts/run-checks.sh`.
6. Perbarui `docs/` bila keputusan desain berubah.

## Referensi dan kejujuran

- Jangan mengarang capability Laravel 12. Verifikasi lewat kode vendor atau dokumentasi resmi.
- Jika menemukan ketidaksesuaian antara spreadsheet dan implementasi (misalnya judul "e-ARSIP" pada backlog DTY), catat dan tanyakan, jangan diam-diam mengikuti yang salah.
- Panduan kolaborasi manusia (branch, PR, serah terima sprint) ada di `CONTRIBUTING.md`; ikuti alur branch/PR yang sama bila diminta membuat commit atau PR.
