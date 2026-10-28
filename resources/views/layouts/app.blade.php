<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Beranda') — PMT</title>
    <style>[x-cloak]{display:none!important}</style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full flex-col bg-slate-50 text-slate-900 antialiased" x-data="{ open: false }">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-4 sm:px-6">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                <span class="grid size-9 place-items-center rounded-xl bg-indigo-700 text-sm font-bold text-white">PMT</span>
                <span class="text-base font-semibold tracking-tight">Pelacakan DTY</span>
            </a>

            <nav class="hidden items-center gap-1 text-sm md:flex">
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-lg px-3 py-2 {{ request()->routeIs('dashboard') ? 'bg-slate-100 font-medium text-slate-900' : 'text-slate-600 transition hover:bg-slate-100 hover:text-slate-900' }}">
                        Dashboard
                    </a>

                    @if (auth()->user()->hasRole(\App\Enums\UserRole::Admin))
                        <a href="{{ route('admin.users.index') }}" class="rounded-lg px-3 py-2 {{ request()->routeIs('admin.users.*') ? 'bg-slate-100 font-medium text-slate-900' : 'text-slate-600 transition hover:bg-slate-100 hover:text-slate-900' }}">
                            Pengguna
                        </a>
                    @endif

                    <a href="{{ route('profile.edit') }}" class="rounded-lg px-3 py-2 {{ request()->routeIs('profile.*') ? 'bg-slate-100 font-medium text-slate-900' : 'text-slate-600 transition hover:bg-slate-100 hover:text-slate-900' }}">
                        Profil
                    </a>

                    <span class="mx-2 inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ auth()->user()->role->badgeClasses() }}">
                        {{ auth()->user()->role->label() }}
                    </span>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-lg px-3 py-2 text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                            Keluar
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="rounded-lg bg-indigo-700 px-3.5 py-2 font-medium text-white transition hover:bg-indigo-800">
                        Masuk
                    </a>
                @endauth
            </nav>

            <button type="button" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 md:hidden" x-on:click="open = ! open" aria-label="Buka menu">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                </svg>
            </button>
        </div>

        <div class="border-t border-slate-200 md:hidden" x-cloak x-show="open">
            <div class="mx-auto flex max-w-6xl flex-col gap-1 px-4 py-3 text-sm sm:px-6">
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-100">Dashboard</a>

                    @if (auth()->user()->hasRole(\App\Enums\UserRole::Admin))
                        <a href="{{ route('admin.users.index') }}" class="rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-100">Pengguna</a>
                    @endif

                    <a href="{{ route('profile.edit') }}" class="rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-100">Profil</a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-slate-600 hover:bg-slate-100">Keluar</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="rounded-lg px-3 py-2 font-medium text-indigo-700 hover:bg-slate-100">Masuk</a>
                @endauth
            </div>
        </div>
    </header>

    <main class="flex-1">
        <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
            @if (session('status'))
                <div class="mb-6 flex items-start justify-between gap-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" x-data="{ show: true }" x-show="show">
                    <p>{{ session('status') }}</p>
                    <button type="button" class="text-emerald-700 hover:text-emerald-900" x-on:click="show = false" aria-label="Tutup">&times;</button>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    <p class="font-medium">Ada data yang perlu diperbaiki.</p>
                    <ul class="mt-1 list-inside list-disc space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-6 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <p>&copy; {{ date('Y') }} PMT. Sistem pelacakan rol benang DTY.</p>
            <p>Sprint 1 &mdash; Fondasi &amp; Manajemen Akses.</p>
        </div>
    </footer>
</body>
</html>
