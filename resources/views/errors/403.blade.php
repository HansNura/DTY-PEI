@extends('layouts.app')

@section('title', 'Akses Ditolak')

@section('content')
    <div class="mx-auto max-w-lg rounded-2xl border border-rose-200 bg-white p-8 text-center shadow-sm">
        <p class="text-sm font-semibold uppercase tracking-wide text-rose-600">403</p>
        <h1 class="mt-2 text-xl font-semibold text-slate-900">Akses ditolak</h1>
        <p class="mt-2 text-sm text-slate-600">
            Peran Anda tidak memiliki izin untuk membuka halaman ini. Hubungi administrator jika akses ini diperlukan.
        </p>
        <a href="{{ auth()->check() ? route('dashboard') : route('home') }}" class="mt-6 inline-flex rounded-lg bg-indigo-700 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-indigo-800">
            Kembali
        </a>
    </div>
@endsection
