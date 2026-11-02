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
<body class="min-h-full bg-canvas text-slate-900 antialiased">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-brand-600 focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:text-white">
        Lewati ke konten
    </a>

    @auth
        @php
            $currentUser = auth()->user();
            $navigationGroups = \App\Support\Navigation::groups($currentUser);
            $bottomNavigation = \App\Support\Navigation::bottom($currentUser);
        @endphp

        <div class="min-h-screen lg:flex" x-data="{ navOpen: false }">
            @include('layouts.partials.sidebar', ['groups' => $navigationGroups, 'user' => $currentUser])

            <div x-cloak x-show="navOpen" class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true">
                <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-[2px]" x-on:click="navOpen = false"></div>
                <div class="relative flex h-full w-72 max-w-[85vw] flex-col bg-white shadow-xl" x-on:keydown.escape.window="navOpen = false">
                    <div class="flex h-16 items-center justify-between border-b border-slate-100 px-5">
                        <x-app-logo />
                        <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100" x-on:click="navOpen = false" aria-label="Tutup navigasi">
                            <x-icon name="x" class="size-5" />
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto px-3 py-4">
                        @foreach ($navigationGroups as $group)
                            <p class="px-2 pb-2 pt-4 text-[10px] font-semibold uppercase tracking-wider text-slate-400">{{ $group['label'] }}</p>
                            <ul class="space-y-1">
                                @foreach ($group['items'] as $item)
                                    @php $active = request()->routeIs($item['active']); @endphp
                                    <li>
                                        <a href="{{ route($item['route']) }}" x-on:click="navOpen = false"
                                           class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm {{ $active ? 'bg-brand-50 font-medium text-brand-700' : 'text-slate-600 hover:bg-slate-50' }}">
                                            <x-icon :name="$item['icon']" class="size-5 {{ $active ? 'text-brand-600' : 'text-slate-400' }}" />
                                            {{ $item['label'] }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="flex min-w-0 flex-1 flex-col">
                @include('layouts.partials.topbar', ['user' => $currentUser])

                <main id="main-content" class="flex-1 px-4 pb-28 pt-6 sm:px-6 lg:px-8 lg:pb-10">
                    <div class="mx-auto max-w-7xl space-y-6">
                        @if (session('status'))
                            <x-alert tone="success" dismissible>{{ session('status') }}</x-alert>
                        @endif

                        @if ($errors->any())
                            <x-alert tone="danger" title="Ada data yang perlu diperbaiki">
                                <ul class="list-inside list-disc space-y-0.5">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </x-alert>
                        @endif

                        @yield('content')
                    </div>
                </main>

                @include('layouts.partials.bottom-nav', ['items' => $bottomNavigation])
            </div>
        </div>
    @else
        <div class="flex min-h-screen flex-col">
            <header class="border-b border-slate-200/80 bg-white/90 backdrop-blur">
                <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
                    <x-app-logo />
                    @unless (request()->routeIs('login'))
                        <a href="{{ route('login') }}" class="rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-brand-700">
                            Masuk
                        </a>
                    @endunless
                </div>
            </header>

            <main id="main-content" class="flex-1">
                <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
                    @if (session('status'))
                        <x-alert tone="success" dismissible>{{ session('status') }}</x-alert>
                    @endif

                    @yield('content')
                </div>
            </main>

            <footer class="border-t border-slate-200/80 bg-white">
                <div class="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-6 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <p>&copy; {{ date('Y') }} PMT. Sistem pelacakan rol benang DTY.</p>
                    <p>Gudang &rarr; perjalanan &rarr; pelanggan.</p>
                </div>
            </footer>
        </div>
    @endauth

    @stack('scripts')
</body>
</html>
