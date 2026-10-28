# Aturan Arsitektur

## Prinsip umum

- **Controller tipis.** Controller hanya mengatur alur request/response. Validasi di FormRequest, logika bisnis di service, query kompleks di model/scope.
- **Satu tanggung jawab per kelas.** Jangan menaruh generator QR, alokasi DO, dan pembuatan PDF dalam satu controller.
- **Enum untuk nilai tertutup.** Status dan peran memakai PHP enum (contoh: `App\Enums\UserRole`). Jangan memakai string magis yang tersebar.
- **Route diberi nama.** Semua route memakai `->name(...)`, gunakan `route()` di view dan redirect.
- **Otorisasi berlapis**: middleware `role` untuk akses area, FormRequest `authorize()` untuk aksi, dan policy hanya jika aturan bergantung pada record.

## Struktur yang diharapkan

```text
app/
├── Enums/                 # UserRole, RollStatus, DeliveryOrderStatus, ...
├── Http/
│   ├── Controllers/       # tipis, satu resource per controller
│   ├── Middleware/        # lintas-potong: role, active user, security header
│   └── Requests/          # validasi + otorisasi input
├── Models/                # relasi, scope, cast
├── Policies/              # aturan per-record (mulai Sprint 3)
└── Services/              # logika domain multi-langkah (mulai Sprint 2)
```

Service dibuat hanya ketika ada alur yang benar-benar multi-langkah, misalnya:

- `QrCodeService` — membuat token unik dan render QR.
- `DeliveryOrderService` — menerbitkan DO dan mengalokasikan rol dalam satu transaksi.
- `LoadScanService` — memvalidasi hasil scan terhadap alokasi DO.
- `ReportService` — menyusun rekap lintas tabel.

Jangan membuat service untuk operasi CRUD satu tabel.

## Konvensi penamaan

| Konteks | Konvensi | Contoh |
| --- | --- | --- |
| Kelas & method | Inggris, PascalCase/camelCase | `DeliveryOrderService::issue()` |
| Kolom domain | Indonesia, snake_case | `nomor_lot`, `berat_gram` |
| Route name | dot-case | `admin.users.index`, `logistik.orders.store` |
| View | folder mengikuti area | `resources/views/admin/users/index.blade.php` |
| Test | perilaku, bahasa Inggris | `test_admin_can_create_a_user` |

## Alur request standar

```text
Route (middleware role)
  → FormRequest (validasi + authorize)
  → Controller (orkestrasi)
  → Service (transaksi/logika domain)
  → Model (persistensi)
  → Redirect/View
```

## Transaksi dan konsistensi

Gunakan `DB::transaction()` untuk operasi yang menyentuh lebih dari satu tabel, terutama:

- Penerbitan DO + alokasi `delivery_order_items`.
- Scan muatan + perubahan status rol.
- Konfirmasi POD + status DO menjadi `received`.

## Error dan pesan

- Validasi input memakai pesan Indonesia di `messages()` FormRequest.
- Pelanggaran otorisasi memakai `abort(403, '...')` lewat middleware.
- Jangan menelan exception domain; biarkan Laravel mencatatnya, tampilkan pesan yang aman ke pengguna.

## Yang harus dihindari

- Query Eloquent di dalam Blade.
- `auth()->user()->role === 'admin'` berulang; gunakan `hasRole()` atau middleware.
- Logika status tersebar sebagai string.
- Menambah dependency baru tanpa alasan yang jelas dan tanpa memperbarui README.
