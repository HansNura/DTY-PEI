# Laporan Sprint 5 — Bukti Terima Digital & Rekap Pengiriman

Tiket: **DTY-010, DTY-011**
Status: selesai dan terintegrasi dengan redesain UI

## Lingkup

| Tiket | Modul | Hasil |
| --- | --- | --- |
| DTY-010 | Bukti Terima (POD) | Driver memindai seluruh QR rol, mencatat nama penerima, dan mengambil tanda tangan digital pelanggan |
| DTY-011 | Laporan Pengiriman | Rekap pengiriman dengan filter periode, lot, dan armada; ekspor PDF dan Excel |

## Implementasi POD

- Tabel `proof_of_deliveries` menyimpan satu POD per DO, nama penerima, path tanda tangan, dan waktu terima.
- Tabel `delivery_order_receipt_scans` mencatat setiap scan penerimaan: `valid`, `wrong_do`, `duplicate`, atau `unknown`.
- `ProofOfDeliveryService` memvalidasi:
  - seluruh QR cocok tepat dengan rol pada DO,
  - tidak ada QR berulang atau tidak dikenal,
  - tanda tangan berupa PNG valid dengan ukuran wajar.
- POD hanya disimpan setelah semua validasi lolos. Dalam satu transaksi, rol berubah menjadi `delivered` dan DO menjadi `received`.
- Tanda tangan disimpan di disk privat dan hanya dapat diakses lewat route terautentikasi untuk Logistik atau driver yang ditugaskan.
- Form penerimaan di halaman driver memakai kamera (ZXing), input scanner, dan kanvas tanda tangan; seluruh logika berjalan di `resources/js/app.js`.

## Implementasi laporan

- `DeliveryReportService` memfilter DO berdasarkan tanggal, nomor lot, dan armada.
- Halaman `/logistics/reports/deliveries` menampilkan rekap dan menyediakan unduhan:
  - PDF melalui `barryvdh/laravel-dompdf`,
  - Excel melalui `phpoffice/phpspreadsheet`.
- DO juga dapat diunduh sebagai PDF dari halaman detail, termasuk tanda tangan POD bila sudah ada.

## Bukti uji

`tests/Feature/DeliveryWorkflowTest.php`:
- penerbitan DO dan unduhan PDF,
- driver memperbarui status berurutan, memindai semua rol, dan menyimpan tanda tangan,
- penolakan QR salah, duplikat, tidak dikenal, dan rol yang belum lengkap,
- kapasitas armada,
- filter dan ekspor rekap PDF/Excel,
- driver tidak dapat membuka DO driver lain.

`tests/Feature/DriverDeliveryTest.php` dan `UiSmokeTest` tetap lulus setelah integrasi.

## Batasan yang diketahui

- Tanda tangan disimpan sebagai PNG di disk lokal; produksi perlu memastikan backup disk privat.
- Belum ada foto barang atau geolokasi POD.
- Status perjalanan tetap mengikuti aturan Sprint 4 (semua rol harus dimuat sebelum berangkat).
- Format laporan mengikuti kolom yang disepakati pada Sprint 5; perubahan kolom perlu penyesuaian PDF/Excel dan test.
