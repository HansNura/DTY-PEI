# Laporan Sprint 3 — Surat Jalan & Scanner Muatan

Tiket: **DTY-006, DTY-007**
Status: selesai dan diperbaiki setelah review

## Lingkup

| Tiket | Modul | Hasil |
| --- | --- | --- |
| DTY-006 | Surat Jalan (DO) | Terbitkan DO dengan pelanggan, armada, driver, dan alokasi rol; cetak; batalkan |
| DTY-007 | Scanner Muatan | Validasi QR rol ke DO, deteksi salah DO/duplikat/tidak dikenal, progres muatan |

## Alur status

```text
issued ──scan──> loading ──(driver)──> departed ──> in_transit ──> arrived ──> received
   │                  │
   └──── cancelled <──┘
```

- Penerbitan DO mengubah rol `in_stock` menjadi `allocated` dalam satu transaksi.
- Scan valid mengubah rol menjadi `loaded` dan DO menjadi `loading`.
- Pembatalan DO mengembalikan rol ke `in_stock`; hanya boleh sebelum armada berangkat.

## Perbaikan hasil review

1. **Guard status DO pada scan.** `LoadScanService` dan `LoadScanController@scanner` menolak DO yang bukan `issued`/`loading`, sehingga DO batal tidak dapat mengubah rol menjadi `loaded`.
2. **Validasi status rol.** Hanya rol `allocated`/`loaded` yang diproses; status lain ditolak.
3. **Validasi kapasitas armada.** Total berat rol dibandingkan dengan `fleets.kapasitas_kg` sebelum DO diterbitkan.
4. **Progres scanner** dihitung dari status rol, bukan riwayat scan.
5. **Kamera QR** memakai `BarcodeDetector` bila tersedia, dengan fallback input manual/scanner USB.
6. **Isolasi test** melalui `tests/bootstrap.php` agar `php artisan test` selalu memakai SQLite in-memory.

## Bukti uji

`tests/Feature/DeliveryOrderTest.php`:
- pembuatan DO dan alokasi rol,
- nomor DO otomatis dan unik,
- rol tidak dapat dialokasikan ke dua DO,
- pembatalan mengembalikan stok,
- **kapasitas armada ditolak bila terlampaui**.

`tests/Feature/LoadScanTest.php`:
- scan valid, salah DO, duplikat, tidak dikenal,
- **DO batal tidak dapat di-scan**,
- **halaman scanner DO batal ditolak 403**,
- rol berstatus tidak valid tidak menjadi `loaded`.

## Batasan yang diketahui

- `generateDoNumber()` belum aman untuk dua penerbitan bersamaan; constraint unik mencegah data ganda tetapi request kedua gagal.
- Kapasitas dihitung dari berat rol, bukan volume atau dimensi.
- Kamera membutuhkan HTTPS/localhost dan browser yang mendukung `BarcodeDetector`.
