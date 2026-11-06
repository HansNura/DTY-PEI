# ERD Roadmap — Sistem Pelacakan DTY (PMT)

Dokumen ini menjadi acuan skema basis data lintas sprint. Sprint 1 sampai Sprint 5 sudah dimigrasikan; tabel Sprint 6 masih berupa rancangan dan ditambahkan saat tiket terkait dikerjakan.

## Konvensi

- Nama tabel `snake_case` jamak, primary key `id` big integer.
- Setiap tabel operasional memiliki `created_at` dan `updated_at`.
- Status menggunakan string enum yang divalidasi di level aplikasi.
- Relasi pengguna memakai `users.id`; penghapusan akun dilakukan dengan menonaktifkan (`is_active`), bukan menghapus data historis.

## Sprint 1 — Auth & User (sudah diimplementasikan)

### `users`

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| id | bigint PK | |
| nama | varchar(100) | Nama lengkap |
| email | varchar(150) unique | Disimpan huruf kecil |
| password | varchar(255) | Hash bcrypt |
| role | varchar(20) index | `admin`, `admin_gudang`, `logistik`, `driver` |
| is_active | boolean | Akun nonaktif tidak dapat login |
| last_login_at | timestamp nullable | Diperbarui saat login berhasil |
| remember_token | varchar(100) nullable | |
| created_at / updated_at | timestamp | |

## Sprint 2 — Master Data, QR, Pelanggan, Armada (sudah diimplementasikan)

### `dty_cartons`

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| id | bigint PK | |
| nomor_hu | varchar(50) unique | Carton / HU number, contoh `ADMG00054918` |
| nomor_merge | varchar(50) index | Lot / merge number, contoh `4D9164` |
| grade | varchar(10) | Grade produk, contoh `WE` |
| production_date | date index | Tanggal produksi |
| denier | integer | Denier benang |
| filamen | integer | Jumlah filamen |
| intermingled | boolean | `false` = NI (Non-Intermingled) |
| twist_direction | varchar(1) nullable | `Z` atau `S` |
| gross_weight_kg | decimal(10,2) | Berat kotor kardus + isi |
| net_weight_kg | decimal(10,2) | Berat bersih benang |
| unit_bobbins | smallint | Jumlah bobbin dalam kardus |
| pallet_number | varchar(50) nullable | Nomor pallet, contoh `ADP063296` |
| qr_token | varchar(64) unique | QR identitas kardus |
| catatan | text nullable | Catatan operator |
| created_by | bigint FK users | Admin gudang pencatat |
| created_at / updated_at | timestamp | |

Satu kardus membuat `unit_bobbins` baris `dty_rolls` secara otomatis. Berat per bobbin = `net_weight_kg × 1000 ÷ unit_bobbins`.

### `dty_rolls`

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| id | bigint PK | |
| dty_carton_id | bigint FK dty_cartons nullable | Kardus asal; null untuk input manual lama |
| kode_rol | varchar(50) unique | Kode internal rol |
| denier | integer | Spesifikasi DTY |
| filamen | integer | Jumlah filamen |
| berat_gram | decimal(10,2) | Berat bersih |
| nomor_lot | varchar(50) index | Nomor lot produksi (mengikuti merge kardus) |
| qr_token | varchar(64) unique | Payload QR code |
| status | varchar(20) index | `in_stock`, `allocated`, `loaded`, `delivered`, `cancelled` |
| created_by | bigint FK users | Admin gudang pencatat |
| created_at / updated_at | timestamp | |

Relasi: `created_by` → `users.id`, `dty_carton_id` → `dty_cartons.id`. Satu rol memiliki satu QR unik.
`kode_rol` dan `qr_token` dibuat otomatis dan unik. QR membawa token acak 256-bit; gulungan hanya dapat dibatalkan saat masih `in_stock`, tanpa menghapus riwayatnya.

### `customers`

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| id | bigint PK | |
| kode_pelanggan | varchar(30) unique nullable | Dibuat otomatis oleh sistem saat pelanggan dibuat (format `CUST-0001`) |
| nama_pabrik | varchar(150) | Nama pelanggan |
| email | varchar(150) nullable | Email perusahaan / kontak |
| npwp | varchar(30) nullable | NPWP pelanggan |
| alamat | text | Alamat pengiriman |
| kota | varchar(100) nullable | Kota / kabupaten |
| provinsi | varchar(100) nullable | Provinsi |
| kode_pos | varchar(10) nullable | Kode pos |
| kontak_nama | varchar(100) | PIC penerima |
| kontak_jabatan | varchar(100) nullable | Jabatan PIC |
| kontak_telepon | varchar(30) | |
| latitude | decimal(10,7) nullable | Titik maps |
| longitude | decimal(10,7) nullable | Titik maps |
| catatan | text nullable | Catatan logistik |
| is_active | boolean | |
| created_at / updated_at | timestamp | |

Pelanggan dinonaktifkan melalui `is_active`; data tidak dihapus agar aman untuk relasi pengiriman. Titik koordinat dipilih lewat peta Leaflet pada form (klik peta, geser marker, atau input koordinat manual).

### `fleets`

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| id | bigint PK | |
| kode_armada | varchar(30) unique nullable | Dibuat otomatis oleh sistem saat armada dibuat (format `ARM-0001`) |
| nomor_polisi | varchar(20) unique | |
| jenis_kendaraan | varchar(50) | |
| kapasitas_kg | decimal(10,2) | |
| driver_id | bigint FK users nullable | Driver utama |
| is_active | boolean | |
| created_at / updated_at | timestamp | |

Relasi: `driver_id` → `users.id` (role `driver`).
Armada dinonaktifkan melalui `is_active`; pengemudi yang dapat dipilih harus memiliki akun driver aktif.

## Sprint 3 — Surat Jalan & Scanner Muatan (sudah diimplementasikan)

### `delivery_orders`

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| id | bigint PK | |
| nomor_do | varchar(50) unique | Nomor surat jalan |
| customer_id | bigint FK customers | Tujuan |
| fleet_id | bigint FK fleets | Armada |
| driver_id | bigint FK users | Driver |
| tanggal | date | Tanggal terbit |
| status | varchar(20) index | `issued`, `departed`, `in_transit`, `arrived`, `received` |
| catatan | text nullable | |
| created_by | bigint FK users | Tim logistik |
| created_at / updated_at | timestamp | |

### `delivery_order_items`

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| id | bigint PK | |
| delivery_order_id | bigint FK delivery_orders | |
| dty_roll_id | bigint FK dty_rolls | |
| berat_kg | decimal(10,2) | Berat saat alokasi |
| created_at / updated_at | timestamp | |

Constraint unik pada `dty_roll_id` mencegah satu rol dialokasikan ke lebih dari satu DO.
Setiap rol hanya dapat dialokasikan ke satu DO. Penerbitan DO mengunci stok, memeriksa kapasitas armada, lalu mengubah status rol menjadi `allocated` dalam satu transaksi.

### `load_scans`

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| id | bigint PK | |
| delivery_order_id | bigint FK delivery_orders | |
| dty_roll_id | bigint FK dty_rolls | |
| scanned_by | bigint FK users | Checker gudang |
| result | varchar(20) | `valid`, `wrong_do`, `duplicate`, `unknown` |
| scanned_at | timestamp | |
| created_at / updated_at | timestamp | |

## Sprint 4 — Driver Interface & Live Tracking (sudah diimplementasikan)

### `delivery_tracking_events`

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| id | bigint PK | |
| delivery_order_id | bigint FK delivery_orders | |
| status | varchar(20) index | `departed`, `in_transit`, `arrived` |
| latitude | decimal(10,7) nullable | Posisi saat update |
| longitude | decimal(10,7) nullable | Posisi saat update |
| recorded_by | bigint FK users | Driver |
| recorded_at | timestamp | |
| created_at / updated_at | timestamp | |

Posisi terakhir armada = event terbaru per `delivery_order_id`.
Driver yang ditugaskan memperbarui status berurutan: `issued` → `departed` → `in_transit` → `arrived`.

## Sprint 5 — Bukti Terima & Laporan (sudah diimplementasikan)

### `proof_of_deliveries`

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| id | bigint PK | |
| delivery_order_id | bigint FK delivery_orders unique | Satu POD per DO |
| received_by_name | varchar(100) | Nama penerima |
| signature_path | varchar(255) | Path berkas tanda tangan |
| received_at | timestamp | |
| latitude | decimal(10,7) nullable | |
| longitude | decimal(10,7) nullable | |
| created_at / updated_at | timestamp | |

Tanda tangan PNG disimpan pada disk privat, bukan di disk publik. Akses gambar hanya melalui route terautentikasi untuk Logistik atau driver yang ditugaskan.

### `delivery_order_receipt_scans`

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| id | bigint PK | |
| delivery_order_id | bigint FK delivery_orders | |
| dty_roll_id | bigint FK dty_rolls nullable | Null untuk QR yang tidak dikenal |
| scanned_by | bigint FK users | Driver yang memproses penerimaan |
| result | varchar(20) | `valid`, `wrong_do`, `duplicate`, `unknown` |
| scanned_at | timestamp | |
| created_at / updated_at | timestamp | |

Setiap scan penerimaan dicatat. POD hanya disimpan setelah seluruh QR rol cocok tepat dengan isi DO; penerimaan mengubah status rol menjadi `delivered` dan status DO menjadi `received` dalam satu transaksi. Laporan PDF dan Excel diturunkan dari data DO dan POD, tanpa tabel rekap terpisah.

## Sprint 6 — Audit & Riwayat (rancangan)

### `audit_logs`

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| id | bigint PK | |
| actor_user_id | bigint FK users nullable | Pelaku aksi |
| action | varchar(50) index | Contoh: `CREATE_USER`, `DEACTIVATE_USER` |
| auditable_type | varchar(100) nullable | Model terkait |
| auditable_id | bigint nullable | ID model terkait |
| before | json nullable | Snapshot sebelum perubahan |
| after | json nullable | Snapshot sesudah perubahan |
| ip_address | varchar(45) nullable | |
| user_agent | text nullable | |
| created_at | timestamp | Tanpa `updated_at` |

Riwayat perjalanan rol = gabungan `load_scans`, `delivery_order_items`, `delivery_tracking_events`, dan `proof_of_deliveries` yang difilter per `dty_roll_id`.

## Ringkasan Relasi

```text
users 1─* dty_rolls (created_by)
users 1─* dty_cartons (created_by)
dty_cartons 1─* dty_rolls
users 1─* fleets (driver_id)
users 1─* delivery_orders (driver_id, created_by)
users 1─* load_scans (scanned_by)
users 1─* delivery_tracking_events (recorded_by)
users 1─* audit_logs (actor_user_id)

customers 1─* delivery_orders
fleets    1─* delivery_orders
delivery_orders 1─* delivery_order_items *─1 dty_rolls
delivery_orders 1─* load_scans            *─1 dty_rolls
delivery_orders 1─* delivery_tracking_events
delivery_orders 1─1 proof_of_deliveries
delivery_orders 1─* delivery_order_receipt_scans *─1 dty_rolls
```
