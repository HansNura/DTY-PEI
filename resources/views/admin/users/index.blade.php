@extends('layouts.app')

@section('title', 'Pengguna')

@section('content')
    <div class="space-y-6">
        <x-page-header title="Manajemen pengguna" description="Kelola akun dan hak akses Administrator, Admin Gudang, Tim Logistik, dan Driver.">
            <x-slot:actions>
                <x-button href="{{ route('admin.users.create') }}" icon="plus">Tambah pengguna</x-button>
            </x-slot:actions>
        </x-page-header>

        <x-card padding="p-4">
            <form method="GET" action="{{ route('admin.users.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1.6fr_1fr_1fr_auto]">
                <x-field name="q" class="sm:col-span-2 lg:col-span-1">
                    <x-input id="q" name="q" type="search" value="{{ $search }}" placeholder="Cari nama atau email" aria-label="Cari pengguna" />
                </x-field>

                <x-field name="role">
                    <x-select id="role" name="role" aria-label="Filter peran">
                        <option value="">Semua peran</option>
                        @foreach ($roles as $roleOption)
                            <option value="{{ $roleOption->value }}" @selected($role === $roleOption->value)>{{ $roleOption->label() }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <x-field name="status">
                    <x-select id="status" name="status" aria-label="Filter status">
                        <option value="">Semua status</option>
                        <option value="active" @selected($status === 'active')>Aktif</option>
                        <option value="inactive" @selected($status === 'inactive')>Nonaktif</option>
                    </x-select>
                </x-field>

                <div class="flex items-end gap-2">
                    <x-button type="submit" variant="secondary" icon="filter">Filter</x-button>
                    <x-button href="{{ route('admin.users.index') }}" variant="ghost">Reset</x-button>
                </div>
            </form>
        </x-card>

        <x-table class="hidden md:block" padding="p-0">
            <x-slot:footer>
                <x-pagination :paginator="$users" class="!mt-0" />
            </x-slot:footer>

            <thead class="bg-slate-50/70">
                <tr>
                    <x-th>Pengguna</x-th>
                    <x-th>Peran</x-th>
                    <x-th>Status</x-th>
                    <x-th>Login terakhir</x-th>
                    <x-th align="right">Aksi</x-th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($users as $user)
                    <tr class="hover:bg-slate-50/60">
                        <x-td>
                            <div class="flex items-center gap-3">
                                <x-avatar :name="$user->nama" size="size-9" />
                                <div class="min-w-0">
                                    <p class="truncate font-medium text-slate-900">{{ $user->nama }}</p>
                                    <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                                </div>
                            </div>
                        </x-td>
                        <x-td><x-status-badge :status="$user->role" /></x-td>
                        <x-td><x-badge :tone="$user->is_active ? 'success' : 'danger'" dot>{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge></x-td>
                        <x-td class="tabular-nums">{{ $user->last_login_at?->translatedFormat('d M Y, H:i') ?? 'Belum pernah' }}</x-td>
                        <x-td align="right">
                            <div class="flex items-center justify-end gap-2">
                                <x-button href="{{ route('admin.users.edit', $user) }}" variant="ghost" size="sm" icon="edit">Edit</x-button>
                                @unless ($user->is(auth()->user()))
                                    <x-confirm-button
                                        :action="route('admin.users.toggle-active', $user)"
                                        method="PATCH"
                                        :title="$user->is_active ? 'Nonaktifkan pengguna?' : 'Aktifkan pengguna?'"
                                        :description="$user->is_active ? 'Akun tidak dapat masuk sampai diaktifkan kembali.' : 'Akun akan dapat masuk kembali ke sistem.'"
                                        :confirm-label="$user->is_active ? 'Ya, nonaktifkan' : 'Ya, aktifkan'"
                                        :tone="$user->is_active ? 'danger' : 'brand'"
                                        :variant="$user->is_active ? 'ghost' : 'ghost'"
                                        :icon="$user->is_active ? 'ban' : 'check'"
                                    >
                                        {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </x-confirm-button>
                                @endunless
                            </div>
                        </x-td>
                    </tr>
                @empty
                    <tr><x-td colspan="5" class="py-8 text-center text-slate-500">Tidak ada pengguna yang cocok dengan filter.</x-td></tr>
                @endforelse
            </tbody>
        </x-table>

        <div class="space-y-3 md:hidden">
            @forelse ($users as $user)
                <x-card padding="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <x-avatar :name="$user->nama" size="size-10" />
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $user->nama }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                            </div>
                        </div>
                        <x-badge :tone="$user->is_active ? 'success' : 'danger'" dot>{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge>
                    </div>
                    <div class="mt-3 flex items-center justify-between gap-3">
                        <x-status-badge :status="$user->role" />
                        <span class="text-xs text-slate-500">{{ $user->last_login_at?->translatedFormat('d M Y') ?? 'Belum pernah login' }}</span>
                    </div>
                    <div class="mt-4 flex items-center justify-end gap-2 border-t border-slate-100 pt-3">
                        <x-button href="{{ route('admin.users.edit', $user) }}" variant="secondary" size="sm">Edit</x-button>
                        @unless ($user->is(auth()->user()))
                            <x-confirm-button
                                :action="route('admin.users.toggle-active', $user)"
                                method="PATCH"
                                :title="$user->is_active ? 'Nonaktifkan pengguna?' : 'Aktifkan pengguna?'"
                                :description="$user->is_active ? 'Akun tidak dapat masuk sampai diaktifkan kembali.' : 'Akun akan dapat masuk kembali ke sistem.'"
                                :confirm-label="$user->is_active ? 'Ya, nonaktifkan' : 'Ya, aktifkan'"
                                :tone="$user->is_active ? 'danger' : 'brand'"
                                variant="ghost"
                                :icon="$user->is_active ? 'ban' : 'check'"
                            >
                                {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                            </x-confirm-button>
                        @endunless
                    </div>
                </x-card>
            @empty
                <x-empty-state icon="users" title="Tidak ada pengguna" description="Belum ada pengguna yang cocok dengan filter." />
            @endforelse
            <x-pagination :paginator="$users" />
        </div>
    </div>
@endsection
