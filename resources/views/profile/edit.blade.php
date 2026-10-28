@extends('layouts.app')

@section('title', 'Profil')

@section('content')
    <div class="mx-auto max-w-2xl space-y-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Profil saya</h1>
            <p class="mt-1 text-sm text-slate-500">Perbarui data akun dan kata sandi Anda.</p>
        </div>

        <form method="POST" action="{{ route('profile.update') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')

            <h2 class="text-sm font-semibold text-slate-900">Data akun</h2>

            <div class="mt-4 grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="nama" class="block text-sm font-medium text-slate-700">Nama lengkap</label>
                    <input id="nama" name="nama" type="text" value="{{ old('nama', $user->nama) }}" required
                           class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required
                           class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                </div>
            </div>

            <div class="mt-5 flex items-center justify-between gap-3">
                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $user->role->badgeClasses() }}">
                    {{ $user->role->label() }}
                </span>
                <button type="submit" class="rounded-lg bg-indigo-700 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-indigo-800">
                    Simpan data
                </button>
            </div>
        </form>

        <form method="POST" action="{{ route('profile.password') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')

            <h2 class="text-sm font-semibold text-slate-900">Ubah kata sandi</h2>

            <div class="mt-4 space-y-5">
                <div>
                    <label for="current_password" class="block text-sm font-medium text-slate-700">Kata sandi saat ini</label>
                    <input id="current_password" name="current_password" type="password" required autocomplete="current-password"
                           class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700">Kata sandi baru</label>
                        <input id="password" name="password" type="password" required autocomplete="new-password"
                               class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Konfirmasi kata sandi baru</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                               class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                    </div>
                </div>
            </div>

            <div class="mt-5 flex justify-end">
                <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800">
                    Ubah kata sandi
                </button>
            </div>
        </form>
    </div>
@endsection
