@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php
        $modules = match ($user->role) {
            \App\Enums\UserRole::Admin => [
                ['DTY-001', 'Auth & User', 'Sprint 1', 'Selesai'],
                ['DTY-012', 'Audit & History', 'Sprint 6', 'Mendatang'],
            ],
            \App\Enums\UserRole::AdminGudang => [
                ['DTY-002', 'Master Data DTY', 'Sprint 2', 'Tersedia'],
                ['DTY-003', 'QR Code Engine', 'Sprint 2', 'Tersedia'],
                ['DTY-007', 'Scanner Muatan', 'Sprint 3', 'Mendatang'],
            ],
            \App\Enums\UserRole::Logistik => [
                ['DTY-004', 'Master Pelanggan', 'Sprint 2', 'Tersedia'],
                ['DTY-005', 'Master Armada', 'Sprint 2', 'Tersedia'],
                ['DTY-006', 'Surat Jalan (DO)', 'Sprint 3', 'Mendatang'],
                ['DTY-009', 'Live Tracking', 'Sprint 4', 'Mendatang'],
                ['DTY-011', 'Laporan Pengiriman', 'Sprint 5', 'Mendatang'],
            ],
            \App\Enums\UserRole::Driver => [
                ['DTY-008', 'Driver Interface', 'Sprint 4', 'Mendatang'],
                ['DTY-010', 'Bukti Terima (POD)', 'Sprint 5', 'Mendatang'],
            ],
        };
    @endphp

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Selamat datang, {{ $user->nama }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $user->role->description() }}</p>
        </div>
        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium ring-1 ring-inset {{ $user->role->badgeClasses() }}">
            {{ $user->role->label() }}
        </span>
    </div>

    @if ($user->hasRole(\App\Enums\UserRole::Admin))
        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Total pengguna</p>
                <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">{{ $totalUsers }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Akun aktif</p>
                <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">{{ $activeUsers }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Akun nonaktif</p>
                <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">{{ $totalUsers - $activeUsers }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">Peran terdaftar</p>
                <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">{{ count(\App\Enums\UserRole::cases()) }}</p>
            </div>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-[1.4fr_1fr]">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <h2 class="text-sm font-semibold text-slate-900">Pengguna terbaru</h2>
                    <a href="{{ route('admin.users.index') }}" class="text-sm font-medium text-indigo-700 hover:text-indigo-900">Kelola</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-5 py-3 font-medium">Nama</th>
                                <th class="px-5 py-3 font-medium">Peran</th>
                                <th class="px-5 py-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($latestUsers as $latestUser)
                                <tr>
                                    <td class="px-5 py-3">
                                        <p class="font-medium text-slate-900">{{ $latestUser->nama }}</p>
                                        <p class="text-xs text-slate-500">{{ $latestUser->email }}</p>
                                    </td>
                                    <td class="px-5 py-3">
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $latestUser->role->badgeClasses() }}">
                                            {{ $latestUser->role->label() }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3">
                                        @if ($latestUser->is_active)
                                            <span class="text-emerald-700">Aktif</span>
                                        @else
                                            <span class="text-rose-700">Nonaktif</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-5 py-6 text-center text-slate-500">Belum ada pengguna.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-900">Sebaran peran</h2>
                <dl class="mt-4 space-y-3">
                    @foreach (\App\Enums\UserRole::cases() as $role)
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-sm text-slate-600">{{ $role->label() }}</dt>
                            <dd class="text-sm font-semibold text-slate-900">{{ $roleCounts[$role->value] ?? 0 }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </div>
    @endif

    <div class="mt-8 grid gap-6 lg:grid-cols-[1fr_1.4fr]">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">Akun Anda</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-slate-500">Email</dt>
                    <dd class="font-medium text-slate-900">{{ $user->email }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-slate-500">Peran</dt>
                    <dd class="font-medium text-slate-900">{{ $user->role->label() }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-slate-500">Status</dt>
                    <dd class="font-medium {{ $user->is_active ? 'text-emerald-700' : 'text-rose-700' }}">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-slate-500">Login terakhir</dt>
                    <dd class="font-medium text-slate-900">{{ $user->last_login_at?->translatedFormat('d M Y, H:i') ?? 'Belum pernah' }}</dd>
                </div>
            </dl>
            <a href="{{ route('profile.edit') }}" class="mt-5 inline-flex text-sm font-medium text-indigo-700 hover:text-indigo-900">Ubah profil</a>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">Modul terkait peran Anda</h2>
            <ul class="mt-4 divide-y divide-slate-100">
                @foreach ($modules as [$ticket, $module, $sprint, $state])
                    <li class="flex items-center justify-between gap-3 py-3">
                        <div>
                            <p class="text-sm font-medium text-slate-900">{{ $module }}</p>
                            <p class="text-xs text-slate-500">{{ $ticket }} &middot; {{ $sprint }}</p>
                        </div>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ in_array($state, ['Selesai', 'Tersedia'], true) ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-slate-50 text-slate-600 ring-slate-200' }}">
                            {{ $state }}
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endsection
