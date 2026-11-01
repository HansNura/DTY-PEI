# Laporan Sprint 4 — Driver Interface & Live Tracking

Tiket: **DTY-008, DTY-009**
Status: selesai

## Lingkup

| Tiket | Modul | Hasil |
| --- | --- | --- |
| DTY-008 | Driver Interface | Daftar perjalanan driver, detail DO, update status Berangkat/Dalam Perjalanan/Tiba |
| DTY-009 | Live Tracking | Dashboard logistik dengan peta posisi terakhir, status armada, dan riwayat perjalanan |

## Implementasi

### Data

Tabel `delivery_tracking_events`:

| Kolom | Keterangan |
| --- | --- |
| delivery_order_id | DO terkait |
| status | `departed`, `in_transit`, `arrived` |
| latitude / longitude | Opsional, diambil dari GPS perangkat |
| recorded_by | Driver yang memperbarui |
| recorded_at | Waktu kejadian |

### Aturan transisi

| Aksi | Syarat |
| --- | --- |
| Berangkat | DO `issued`/`loading`, semua rol sudah `loaded` |
| Dalam Perjalanan | DO `departed`/`in_transit`; boleh diulang untuk memperbarui posisi |
| Tiba di Tujuan | DO `departed`/`in_transit` |
| Dibatalkan | Semua transisi ditolak |

- Hanya driver yang ditugaskan (`delivery_orders.driver_id`) yang dapat memperbarui.
- Setiap update membuat event baru; riwayat tidak ditimpa.
- Koordinat wajib dikirim berpasangan dan divalidasi rentangnya.
- Jika GPS ditolak, status tetap tersimpan tanpa koordinat.

### Antarmuka

- Halaman driver mobile-first: `/driver/deliveries` dan `/driver/deliveries/{order}`.
- Dashboard logistik: `/logistics/tracking` menampilkan peta Leaflet + OpenStreetMap, statistik, daftar armada aktif, dan detail per DO.
- Navigasi dan dashboard per peran menampilkan tautan ke modul yang tersedia.

## Bukti uji

`tests/Feature/DriverDeliveryTest.php`:
- driver hanya melihat DO miliknya,
- driver tidak dapat melihat/memperbarui DO driver lain,
- berangkat hanya setelah semua rol dimuat,
- transisi dalam perjalanan dan tiba mencatat koordinat,
- tidak dapat melompat dari `loading` ke `arrived`,
- koordinat harus berpasangan dan valid,
- DO batal tidak dapat diperbarui,
- peran non-driver ditolak.

`tests/Feature/LiveTrackingTest.php`:
- logistik melihat peta dan armada aktif,
- DO yang belum berangkat tidak tampil di peta,
- detail tracking menampilkan riwayat,
- empty state bila belum ada koordinat,
- peran non-logistik ditolak.

## Batasan yang diketahui

- Posisi hanya diperbarui saat driver menekan tombol, bukan streaming GPS latar belakang.
- Peta membutuhkan koneksi ke tile OpenStreetMap.
- Belum ada estimasi waktu tiba (ETA) atau reverse geocoding.
