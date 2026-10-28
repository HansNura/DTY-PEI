# Laporan Sprint 1 — Fondasi & Manajemen Akses

Tiket: **DTY-001 — Auth & User**
Peran PIC: Administrator (Helgi)
Status: selesai di lingkungan lokal

## Lingkup tiket

> Sebagai Admin, saya dapat mengelola akun pengguna dan hak akses (Admin Gudang, Logistik, Driver) agar hak akses data terisolasi.

## Kriteria penerimaan dan bukti

| Kriteria | Implementasi | Bukti uji |
| --- | --- | --- |
| Login dan logout | `AuthController`, throttle 5 percobaan/menit | `AuthenticationTest` |
| Empat peran terdefinisi | `App\Enums\UserRole` | `RoleAccessTest` |
| Hak akses terisolasi | Middleware `role` + `EnsureUserIsActive` | `RoleAccessTest`, `InactiveSessionTest` |
| Admin dapat menambah akun | `Admin\UserController@store` | `AdminUserManagementTest` |
| Admin dapat mengubah akun dan peran | `Admin\UserController@update` | `AdminUserManagementTest` |
| Admin dapat menonaktifkan akun | `Admin\UserController@toggleActive` | `AdminUserManagementTest` |
| Admin tidak dapat mengunci diri sendiri | Validasi pada update dan toggle | `AdminUserManagementTest` |
| Akun nonaktif tidak dapat masuk | Cek `is_active` saat login dan setiap request | `AuthenticationTest`, `InactiveSessionTest` |

## Matriks akses peran

| Peran | Sprint 1 | Modul berikutnya |
| --- | --- | --- |
| Administrator | Manajemen pengguna penuh | Audit & history (Sprint 6) |
| Admin Gudang | Dashboard | Master data DTY, QR engine, scanner muatan |
| Tim Logistik | Dashboard | Pelanggan, armada, surat jalan, live tracking, laporan |
| Driver | Dashboard | Driver interface, bukti terima digital |

## Keputusan teknis

- **Session auth Laravel** dengan hash bcrypt, bukan token API, sesuai kebutuhan aplikasi internal berbasis Blade.
- **RBAC sederhana** memakai kolom `role` dan middleware alias `role`. Policy granular ditambahkan saat modul domain mulai memiliki aturan per-record.
- **Email disimpan huruf kecil** lewat mutator model dan normalisasi request untuk mencegah duplikasi akun.
- **Nonaktifkan, bukan hapus**: akun tidak dihapus agar jejak audit dan relasi historis tetap utuh.
- **Security header dasar** dipasang global melalui middleware `SecurityHeaders`.

## Di luar lingkup Sprint 1

- Audit log formal (DTY-012) — saat ini hanya `last_login_at`.
- Reset kata sandi via email.
- Policy per-record untuk data DTY, pelanggan, armada, dan surat jalan.

## Cara memverifikasi

```bash
php artisan migrate:fresh --seed
php artisan test
vendor/bin/pint --test
npm run build
```
