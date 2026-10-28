# Panduan UI — Blade, Alpine.js, Tailwind CSS

## Prinsip

- **Server-rendered.** Blade menghasilkan HTML; Alpine hanya untuk interaksi lokal (toggle, modal, konfirmasi, filter reaktif).
- **Tailwind utility-first.** Jangan menambah CSS kustom kecuali untuk hal yang tidak mungkin dengan utility.
- **Bahasa Indonesia** untuk semua teks yang dilihat pengguna.
- **Aksesibel**: label pada input, `aria-label` pada tombol ikon, fokus terlihat, kontras memadai.

## Layout dan struktur Blade

- Layout utama: `resources/views/layouts/app.blade.php`.
- Setiap halaman memakai `@extends('layouts.app')`, `@section('title', ...)`, dan `@section('content')`.
- Partial form diletakkan di `_form.blade.php` dan dipakai ulang oleh create/edit.
- Jangan menaruh query database di Blade. Siapkan data di controller.

## Alpine.js

Alpine dimuat dari `resources/js/app.js`:

```js
import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();
```

Aturan:

- Gunakan `x-data`, `x-show`, `x-on`, `x-bind` untuk interaksi ringan.
- Sembunyikan elemen awal dengan `x-cloak`; layout sudah menyediakan `[x-cloak]{display:none!important}`.
- Konfirmasi aksi destruktif memakai Alpine, bukan `onclick` inline:

```blade
<form method="POST" action="..." x-data
      x-on:submit="if (! confirm('Nonaktifkan pengguna ini?')) $event.preventDefault()">
    @csrf
    @method('PATCH')
    <button type="submit">Nonaktifkan</button>
</form>
```

- Jangan memakai Alpine untuk mengambil data server; gunakan route/controller.

## Tailwind

### Palet yang dipakai

- Aksi utama: `indigo-700` / `indigo-800`.
- Netral: `slate-50` (latar), `slate-200` (border), `slate-500` (teks sekunder), `slate-900` (teks utama).
- Sukses: `emerald`.
- Bahaya: `rose`.
- Badge peran memakai `UserRole::badgeClasses()`; jangan menulis ulang kombinasi warna di view.

### Pola komponen

Kartu:

```blade
<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
```

Tombol utama:

```blade
<button class="rounded-lg bg-indigo-700 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-indigo-800">
```

Input:

```blade
<input class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
```

Badge:

```blade
<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $model->status->badgeClasses() }}">
```

### Aturan class

- Urutkan kelas secara konsisten: layout → spacing → tipografi → warna → efek.
- Jangan membuat `@apply` kecuali untuk pola yang benar-benar berulang di banyak tempat.
- Jangan memakai arbitrary value (`[...]`) bila utility standar tersedia.
- Pastikan tampilan responsif: mulai mobile, gunakan `sm:`, `md:`, `lg:`.

## Form dan validasi

- Selalu sertakan `@csrf`; gunakan `@method(...)` untuk PUT/PATCH/DELETE.
- Tampilkan error global di layout dan pesan spesifik di dekat field bila diperlukan.
- Pertahankan input lama dengan `old()`.
- Gunakan `@checked()` dan `@selected()`.

## Flash message

Layout menampilkan `session('status')` dan `$errors->any()`. Controller cukup memakai `->with('status', '...')` atau `->withErrors([...])`.

## Yang harus dihindari

- JavaScript framework tambahan (React/Vue/jQuery) tanpa keputusan arsitektur.
- CSS inline kecuali untuk `[x-cloak]`.
- Duplikasi markup badge/tombol; jadikan partial atau helper enum.
- Teks bahasa Inggris pada UI.
