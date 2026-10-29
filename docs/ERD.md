# ERD Roadmap — Sistem Pelacakan DTY (PMT)

Dokumen ini menjadi acuan skema basis data lintas sprint. Implementasi aktual dilakukan bertahap: Sprint 1 sudah dimigrasikan, tabel sprint berikutnya ditambahkan saat tiket terkait dikerjakan.

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

## Sprint 2 — Master Data, QR, Pelanggan, Armada

### `dty_rolls`

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| id | bigint PK | |
| kode_rol | varchar(50) unique | Kode internal rol |
| denier | integer | Spesifikasi DTY |
| filamen | integer | Jumlah filamen |
| berat_gram | decimal(10,2) | Berat bersih |
| nomor_lot | varchar(50) index | Nomor lot produksi |
| qr_token | varchar(64) unique | Payload QR code |
| status | varchar(20) index | `in_stock`, `allocated`, `loaded`, `delivered`, `cancelled` |
| created_by | bigint FK users | Admin gudang pencatat |
| created_at / updated_at | timestamp | |

Relasi: `created_by` → `users.id`. Satu rol memiliki satu QR unik.
`kode_rol` dan `qr_token` dibuat otomatis dan unik. QR membawa token acak 256-bit; gulungan hanya dapat dibatalkan saat masih `in_stock`, tanpa menghapus riwayatnya.

### `customers`

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| id | bigint PK | |
| nama_pabrik | varchar(150) | Nama pelanggan |
| alamat | text | Alamat pengiriman |
| kontak_nama | varchar(100) | PIC penerima |
| kontak_telepon | varchar(30) | |
| latitude | decimal(10,7) nullable | Titik maps |
| longitude | decimal(10,7) nullable | Titik maps |
| is_active | boolean | |
| created_at / updated_at | timestamp | |

Pelanggan dinonaktifkan melalui `is_active`; data tidak dihapus agar aman untuk relasi pengiriman.

### `fleets`

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| id | bigint PK | |
| nomor_polisi | varchar(20) unique | |
| jenis_kendaraan | varchar(50) | |
| kapasitas_kg | decimal(10,2) | |
| driver_id | bigint FK users nullable | Driver utama |
| is_active | boolean | |
| created_at / updated_at | timestamp | |

Relasi: `driver_id` → `users.id` (role `driver`).
Armada dinonaktifkan melalui `is_active`; pengemudi yang dapat dipilih harus memiliki akun driver aktif.

## Sprint 3 — Surat Jalan & Scanner Muatan

### `delivery_orders`

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| id | bigint PK | |
| nomor_do | varchar(50) unique | Nomor surat jalan |
| customer_id | bigint FK customers | Tujuan |
| fleet_id | bigint FK fleets | Armada |
| driver_id | bigint FK users | Driver |
| tanggal | date | Tanggal terbit |
| status | varchar(20) index | `draft`, `issued`, `loading`, `departed`, `in_transit`, `arrived`, `received`, `cancelled` |
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

Constraint unik: (`delivery_order_id`, `dty_roll_id`) untuk mencegah duplikasi alokasi dalam satu DO.

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

## Sprint 4 — Driver Interface & Live Tracking

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

## Sprint 5 — Bukti Terima & Laporan

### `proof_of_deliveries`

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| id | bigint PK | |
| delivery_order_id | bigint FK delivery_orders unique | Satu POD per DO |
| received_by_name | varchar(100) | Nama penerima |
| signature_path | varchar(255) | Path berkas tanda tangan |
| photo_path | varchar(255) nullable | Foto bukti |
| received_at | timestamp | |
| latitude | decimal(10,7) nullable | |
| longitude | decimal(10,7) nullable | |
| created_at / updated_at | timestamp | |

Laporan pengiriman diturunkan dari `delivery_orders`, `delivery_order_items`, `delivery_tracking_events`, dan `proof_of_deliveries` — tidak memerlukan tabel rekap terpisah.

## Sprint 6 — Audit & Riwayat

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
```
