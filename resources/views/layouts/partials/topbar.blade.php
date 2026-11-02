@props(['user'])

@php $roleTone = \App\Support\StatusTone::for($user->role); @endphp

<header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/90 backdrop-blur">
    <div class="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">
        <button type="button" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" x-on:click="navOpen = true" aria-label="Buka navigasi">
            <x-icon name="menu" class="size-5" />
        </button>

        <div class="min-w-0 flex-1">
            <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">PMT</p>
            <p class="truncate text-sm font-semibold text-slate-900">@yield('title', 'Dashboard')</p>
        </div>

        <span class="hidden text-xs text-slate-500 sm:block">{{ now()->translatedFormat('d M Y') }}</span>
        <x-badge :tone="$roleTone" class="hidden sm:inline-flex">{{ $user->role->label() }}</x-badge>

        <x-dropdown align="right">
            <x-slot:trigger>
                <button type="button" class="flex items-center gap-2 rounded-full p-1 transition hover:bg-slate-100" aria-label="Menu akun">
                    <x-avatar :name="$user->nama" size="size-8" />
                    <x-icon name="chevron-down" class="hidden size-4 text-slate-400 sm:block" />
                </button>
            </x-slot:trigger>

            <div class="border-b border-slate-100 px-3 py-2">
                <p class="truncate text-sm font-medium text-slate-900">{{ $user->nama }}</p>
                <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
            </div>
            <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                <x-icon name="user" class="size-4" /> Profil
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-rose-600 hover:bg-rose-50">
                    <x-icon name="logout" class="size-4" /> Keluar
                </button>
            </form>
        </x-dropdown>
    </div>
</header>
