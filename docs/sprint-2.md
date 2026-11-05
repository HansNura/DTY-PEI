# Laporan Sprint 2 — Master Data, QR, Pelanggan, Armada

Tiket: **DTY-002, DTY-003, DTY-004, DTY-005**
Status: selesai

## Lingkup

| Tiket | Modul | Hasil |
| --- | --- | --- |
| DTY-002 | Master Data DTY | CRUD gulungan dengan denier, filamen, berat gram, dan nomor lot |
| DTY-003 | QR Code Engine | Kode rol otomatis, token QR unik, label thermal 50 × 30 mm |
| DTY-004 | Master Pelanggan | CRUD pelanggan, kontak penerima, koordinat maps, nonaktif tanpa hapus |
| DTY-005 | Master Armada | CRUD armada, jenis kendaraan, kapasitas, driver utama |

## Implementasi

- `DtyRoll` menyimpan `kode_rol` dan `qr_token` unik; keduanya dibuat oleh `QrCodeService`.
- STATUS gulungan: `in_stock`, `allocated`, `loaded`, `delivered`, `cancelled`.
- Label QR dirender di browser memakai pustaka `qrcode` dan siap dicetak pada ukuran thermal.
- Pelanggan menyimpan `latitude`/`longitude` dengan validasi rentang dan presisi database.
- Pelanggan dan armada dinonaktifkan melalui `is_active`, bukan dihapus.
- Driver utama armada wajib akun aktif dengan peran `driver`.

## Bukti uji

`tests/Feature/DtyCartonManagementTest.php`:
- input kardus membuat bobbin dan QR sesuai `unit_bobbins`,
- validasi net ≤ gross, HU unik, dan jumlah bobbin,
- update berat menghitung ulang berat per bobbin,
- kardus yang sudah dialokasikan tidak dapat diubah,
- label kardus menampilkan HU, merge, spesifikasi, dan berat,
- otorisasi khusus Admin Gudang.

`tests/Feature/DtyRollManagementTest.php`:
- `test_warehouse_user_can_create_a_roll_with_a_unique_qr_token_and_print_label`
- `test_warehouse_user_can_update_and_cancel_only_available_rolls`
- `test_allocated_roll_cannot_be_edited_or_cancelled`
- `test_invalid_roll_specification_is_rejected`
- `test_only_warehouse_users_can_access_roll_management`

`tests/Feature/CustomerManagementTest.php`, `tests/Feature/FleetManagementTest.php`:
- validasi koordinat, kapasitas positif, pelat unik, driver aktif, otorisasi peran.

## Di luar lingkup

- Import massal data lama.
- Cetak batch banyak label sekaligus.
