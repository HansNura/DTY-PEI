@extends('layouts.app')

@section('title', 'Akses Ditolak')

@section('content')
    <div class="mx-auto max-w-lg py-10 text-center">
        <span class="mx-auto grid size-16 place-items-center rounded-2xl bg-rose-50 text-rose-600 ring-1 ring-rose-100">
            <x-icon name="lock" class="size-8" />
        </span>
        <p class="mt-6 text-xs font-semibold uppercase tracking-widest text-rose-600">403 — Akses ditolak</p>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">Halaman ini bukan untuk peran Anda</h1>
        <p class="mt-2 text-sm text-slate-500">Peran Anda tidak memiliki izin untuk membuka halaman ini. Hubungi administrator jika akses ini memang diperlukan.</p>

        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <x-button href="{{ auth()->check() ? route('dashboard') : route('home') }}" icon="arrow-left">Kembali</x-button>
            @auth
                <x-button href="{{ route('profile.edit') }}" variant="secondary" icon="user">Lihat profil</x-button>
            @endauth
        </div>
    </div>
@endsection
