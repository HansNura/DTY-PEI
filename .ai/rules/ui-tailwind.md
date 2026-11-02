# Panduan UI — Clean SaaS Light

## Arah desain

Permukaan putih/abu terang, garis tipis, bayangan lembut, aksen teal, dan hierarki tipografi yang jelas. Semua halaman memakai app shell yang sama; jangan membuat pola visual baru per halaman.

- **Server-rendered.** Blade menghasilkan HTML; Alpine hanya untuk interaksi lokal.
- **Komponen dulu.** Susun halaman dari `resources/views/components/`, bukan menulis ulang markup.
- **Bahasa Indonesia** untuk semua teks yang dilihat pengguna.
- **Aksesibel**: label, `aria-label`, fokus terlihat, kontras AA, dan status tidak hanya dibedakan lewat warna.

## Token

Token didefinisikan di `resources/css/app.css` lewat `@theme` Tailwind 4.

| Token | Nilai | Pemakaian |
| --- | --- | --- |
| `brand-600` | `#0d9488` | Tombol utama, tautan aktif, fokus |
| `brand-700` | `#0f766e` | Hover |
| `brand-50` | `#f0fdfa` | Latar aktif, badge lembut |
| `canvas` | `#f7f9fb` | Latar halaman |
| `surface` | putih | Kartu, sidebar, topbar |
| `slate-900` | teks utama | Judul dan nilai |
| `slate-500` | teks sekunder | Deskripsi, meta |
| `slate-200` | border | Kartu, pemisah |
| `emerald` / `amber` / `rose` / `sky` | semantik | Sukses / menunggu / bahaya / info |
| `shadow-card` | custom | Bayangan kartu |
| `font-sans` | Instrument Sans Variable | Self-host via `@fontsource-variable/instrument-sans` |

Jangan memakai `indigo-*` untuk aksi utama lagi. Jangan menulis warna status langsung di view; gunakan `x-status-badge`.

## App shell

- `resources/views/layouts/app.blade.php` memilih shell auth atau guest.
- Auth: sidebar 264px (`layouts/partials/sidebar.blade.php`), topbar (`topbar.blade.php`), bottom nav mobile (`bottom-nav.blade.php`).
- Menu berasal dari `App\Support\Navigation::groups()` dan `::bottom()`. Jangan menduplikasi daftar menu di Blade.
- Halaman auth memakai `pb-28` agar konten tidak tertutup bottom nav.
- Halaman guest memakai header publik sederhana.

## Komponen

Semua di `resources/views/components/`.

| Komponen | Contoh |
| --- | --- |
| `x-page-header` | `<x-page-header title="..." description="..."><x-slot:actions>...</x-slot:actions></x-page-header>` |
| `x-card` | `<x-card title="..." description="..."><x-slot:actions>...</x-slot:actions>isi</x-card>` |
| `x-stat-card` | `<x-stat-card label="..." :value="..." icon="truck" tone="info" />` |
| `x-kpi-grid` | Pembungkus grid KPI |
| `x-button` | `<x-button variant="primary|secondary|ghost|subtle|danger" size="sm|md|lg" icon="..." href="...">` |
| `x-badge` | `<x-badge tone="success" dot>Aktif</x-badge>` |
| `x-status-badge` | `<x-status-badge :status="$order->status" />` |
| `x-field` + `x-input/x-select/x-textarea/x-checkbox` | Kontrol form konsisten |
| `x-alert` | `<x-alert tone="danger" title="...">...</x-alert>` |
| `x-empty-state` | Ikon, judul, deskripsi, slot aksi |
| `x-table/x-th/x-td` | Shell tabel dan sel |
| `x-confirm-button` | Konfirmasi aksi dengan modal Alpine |
| `x-dropdown` | Menu pengguna / aksi |
| `x-progress` | `<x-progress :value="$loaded" :max="$total" />` |
| `x-timeline/x-timeline-item` | Riwayat scan dan perjalanan |
| `x-status-stepper` | Alur status DO |
| `x-avatar` | Inisial pengguna |
| `x-pagination` | `<x-pagination :paginator="$items" />` |
| `x-icon` | `<x-icon name="truck" class="size-5" />` (Heroicons outline) |

Aturan komponen:

- Komponen menerima `class` tambahan lewat `$attributes`.
- Jangan menambah warna hardcoded di halaman; gunakan tone komponen.
- Status memakai `App\Support\StatusTone` agar pemetaan konsisten.

## Tabel dan mobile

- Desktop: `x-table` dengan `class="hidden md:block"`.
- Mobile: daftar `x-card` dengan `md:hidden`.
- Selalu sediakan empty state.
- Angka berat, kapasitas, dan waktu memakai `tabular-nums`.
- Aksi baris memakai `x-button variant="ghost" size="sm"`; konfirmasi memakai `x-confirm-button`.

## Form

- Gunakan `x-field` agar label, hint, dan error konsisten.
- Selalu `@csrf`; `@method(...)` untuk PUT/PATCH/DELETE.
- Pertahankan input lama dengan `old()`; gunakan `@checked()` dan `@selected()`.
- Form panjang dipecah menjadi beberapa `x-card` (Identitas, Akses, Keamanan).
- Tombol simpan di kanan bawah; sediakan Batal.
- Jangan memakai `confirm()` bawaan browser; gunakan `x-confirm-button`.

## Alpine.js

- `x-data` untuk state lokal; `x-show`, `x-bind`, `x-on`, `x-model`.
- `x-cloak` untuk mencegah flash; layout menyediakan CSS-nya.
- Modal/dropdown memakai komponen, bukan markup ad hoc.
- Scanner dan driver mempertahankan logika `submitToken`, kamera, dan geolokasi.
- Jangan memakai Alpine untuk query data server; gunakan route/controller.

## Cetak

- `resources/views/logistics/orders/print.blade.php` adalah halaman cetak mandiri; jangan ubah strukturnya.
- Label thermal memakai teknik `visibility` di `inventory/rolls/label.blade.php`; pertahankan ukuran 50 × 30 mm dan `@page`.
- Jangan menambahkan elemen layout ke area cetak.

## Aksesibilitas dan performa

- Tombol ikon wajib punya `aria-label`.
- Fokus terlihat (`focus-visible:ring-2`).
- Kontras teks minimal AA.
- Hormati `prefers-reduced-motion` (sudah di `app.css`).
- Peta Leaflet dimuat hanya di halaman tracking lewat `resources/js/tracking-map.js`.
- Tidak menambah library chart/icon font; grafik memakai CSS/SVG.

## Yang harus dihindari

- Menulis ulang class kartu/tombol/input di halaman.
- Warna status langsung di view.
- JavaScript framework tambahan (React/Vue/jQuery).
- CSS inline kecuali untuk `[x-cloak]` dan print label.
- Teks bahasa Inggris pada UI.
- Mengubah halaman cetak saat redesain layar.
