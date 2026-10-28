# Panduan Database

## Lingkungan

- Lokal: SQLite (`database/database.sqlite`).
- Produksi: MySQL 8 atau PostgreSQL 15+.
- Jangan memakai fitur SQLite-only pada migrasi. Semua migrasi harus portabel ke MySQL/PostgreSQL.
- Test memakai SQLite in-memory (`phpunit.xml`), jadi hindari perbedaan perilaku antar-driver.

## Migrasi

- Satu migrasi per perubahan skema yang bermakna.
- Jangan mengedit migrasi yang sudah dijalankan di lingkungan bersama; buat migrasi baru.
- Nama file mengikuti `YYYY_MM_DD_HHMMSS_verb_noun_table.php`.
- Selalu tulis `down()` yang benar.
- Foreign key wajib punya `constrained()` dan kebijakan `onDelete` yang eksplisit.
- Gunakan `decimal(10,2)` untuk berat (kg/gram) dan `decimal(10,7)` untuk koordinat. Jangan memakai `float` untuk nilai finansial/berat.

Contoh:

```php
Schema::create('delivery_orders', function (Blueprint $table) {
    $table->id();
    $table->string('nomor_do', 50)->unique();
    $table->foreignId('customer_id')->constrained()->restrictOnDelete();
    $table->foreignId('fleet_id')->constrained()->restrictOnDelete();
    $table->foreignId('driver_id')->constrained('users')->restrictOnDelete();
    $table->date('tanggal');
    $table->string('status', 20)->index();
    $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
    $table->timestamps();
});
```

## Indexing

Index wajib untuk:

- Kolom yang sering difilter: `status`, `tanggal`, `nomor_lot`.
- Foreign key (Laravel sudah membuat index lewat `foreignId`).
- Kombinasi unik bisnis: `unique(['delivery_order_id', 'dty_roll_id'])`.
- Pencarian nama pelanggan/pengguna: pertimbangkan index biasa; hindari `LIKE '%...%'` pada tabel besar tanpa strategi tambahan.

## Status dan enum

- Simpan status sebagai `varchar(20)` dengan validasi di aplikasi (PHP enum).
- Jangan memakai `enum` native MySQL karena menyulitkan perubahan nilai.
- Nilai status harus didokumentasikan di `docs/ERD.md`.

## Soft delete dan retensi

- **Akun pengguna tidak dihapus**, hanya `is_active = false`.
- Data operasional (rol, DO, scan, tracking, POD) bersifat historis: jangan hard-delete. Gunakan status `cancelled` atau kolom pembatalan.
- Soft delete hanya dipakai jika ada kebutuhan pemulihan yang jelas; jangan menambahkannya sebagai default.
- Audit log bersifat append-only: tanpa `updated_at`, tanpa update, tanpa delete.

## Seed dan data demo

- Seeder harus **idempoten** (`updateOrCreate`), aman dijalankan berulang.
- Akun demo hanya dibuat oleh `UserSeeder` dan memakai domain `@pmt.test`.
- Jangan menaruh data produksi di seeder.

## Integritas

- Gunakan transaksi untuk operasi lintas tabel.
- Cegah alokasi ganda rol ke DO berbeda lewat constraint unik dan validasi aplikasi.
- Untuk scan muatan, simpan hasil (`valid`, `wrong_do`, `duplicate`, `unknown`) agar dapat diaudit.

## Perintah

```bash
php artisan migrate
php artisan migrate:fresh --seed
bash .ai/scripts/db-reset.sh   # hanya untuk database lokal
```
