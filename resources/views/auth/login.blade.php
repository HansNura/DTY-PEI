@extends('layouts.app')

@section('title', 'Masuk')

@section('content')
    <div class="mx-auto max-w-md">
        <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
            <h1 class="text-xl font-semibold tracking-tight text-slate-900">Masuk ke PMT</h1>
            <p class="mt-1 text-sm text-slate-500">Gunakan akun yang diberikan administrator.</p>

            <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                           autocomplete="username"
                           class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700">Kata sandi</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password"
                           class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" value="1" class="size-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    Ingat saya
                </label>

                <button type="submit" class="w-full rounded-lg bg-indigo-700 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-indigo-800">
                    Masuk
                </button>
            </form>

            <div class="mt-6 border-t border-slate-200 pt-4" x-data="{ show: false }">
                <button type="button" class="text-sm font-medium text-indigo-700 hover:text-indigo-900" x-on:click="show = ! show">
                    <span x-show="! show">Lihat akun demo</span>
                    <span x-show="show" x-cloak>Sembunyikan akun demo</span>
                </button>

                <div class="mt-3 space-y-1 text-sm text-slate-600" x-cloak x-show="show">
                    <p><span class="font-medium text-slate-900">Administrator:</span> admin@pmt.test</p>
                    <p><span class="font-medium text-slate-900">Admin Gudang:</span> gudang@pmt.test</p>
                    <p><span class="font-medium text-slate-900">Tim Logistik:</span> logistik@pmt.test</p>
                    <p><span class="font-medium text-slate-900">Driver:</span> driver@pmt.test</p>
                    <p class="pt-1 text-xs text-slate-500">Semua akun demo memakai kata sandi <span class="font-mono">password</span>.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
