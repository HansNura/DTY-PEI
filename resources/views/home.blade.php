@extends('layouts.app')

@section('title', 'Beranda')

@section('content')
    <section class="grid gap-10 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
        <div>
            <span class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-200">
                Sprint 1 &mdash; Fondasi &amp; Manajemen Akses
            </span>

            <h1 class="mt-5 text-3xl font-semibold tracking-tight text-slate-900 sm:text-4xl">
                Lacak setiap rol benang DTY dari gudang sampai pelanggan.
            </h1>

            <p class="mt-4 max-w-2xl text-base leading-relaxed text-slate-600">
                PMT adalah sistem pelacakan logistik benang DTY: pencatatan spesifikasi rol, QR code, surat jalan,
                validasi muatan, status perjalanan, bukti terima digital, hingga laporan dan audit.
            </p>

            <div class="mt-8 flex flex-wrap items-center gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-lg bg-indigo-700 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-indigo-800">
                        Buka dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="rounded-lg bg-indigo-700 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-indigo-800">
                        Masuk ke sistem
                    </a>
                @endauth
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Alur kerja</h2>
            <ol class="mt-4 space-y-4">
                @foreach ([
                    ['Input rol DTY', 'Spesifikasi denier, filamen, berat, dan nomor lot.'],
                    ['Cetak QR & label', 'Satu QR unik per gulungan untuk validasi muatan.'],
                    ['Terbitkan surat jalan', 'Pilih armada, tujuan, dan alokasi lot.'],
                    ['Scan & berangkat', 'Checker memindai rol, driver memperbarui status.'],
                    ['Bukti terima', 'Tanda tangan digital pelanggan sebagai POD.'],
                ] as $index => [$title, $description])
                    <li class="flex gap-3">
                        <span class="grid size-7 shrink-0 place-items-center rounded-full bg-slate-100 text-xs font-semibold text-slate-700">{{ $index + 1 }}</span>
                        <div>
                            <p class="text-sm font-medium text-slate-900">{{ $title }}</p>
                            <p class="text-sm text-slate-500">{{ $description }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>
@endsection
