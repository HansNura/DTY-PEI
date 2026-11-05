@extends('layouts.app')

@section('title', 'Pelanggan')

@section('content')
    <div class="space-y-6">
        <x-page-header title="Master pelanggan" description="Kelola kode pelanggan, pabrik tujuan, alamat, kota, kontak penerima, dan titik peta.">
            <x-slot:actions>
                <x-button href="{{ route('logistics.customers.create') }}" icon="plus">Tambah pelanggan</x-button>
            </x-slot:actions>
        </x-page-header>

        <x-card padding="p-3 sm:p-4">
            <form method="GET" action="{{ route('logistics.customers.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-center">
                <div class="relative min-w-0 flex-1">
                    <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                    <x-input id="q" name="q" type="search" value="{{ $search }}" placeholder="Cari kode, nama pabrik, email, kota, atau kontak" aria-label="Cari pelanggan" class="pl-10" />
                </div>

                <fieldset class="w-fit shrink-0 rounded-xl bg-slate-100 p-1">
                    <legend class="sr-only">Status pelanggan</legend>
                    <div class="flex items-center gap-0.5">
                        @foreach (['' => 'Semua', 'active' => 'Aktif', 'inactive' => 'Nonaktif'] as $value => $label)
                            <label class="cursor-pointer select-none">
                                <input type="radio" name="status" value="{{ $value }}" class="peer sr-only" @checked($status === $value) onchange="this.form.requestSubmit()" />
                                <span class="inline-flex items-center rounded-lg px-3 py-1.5 text-xs font-medium text-slate-500 transition hover:text-slate-700 peer-checked:bg-white peer-checked:font-semibold peer-checked:text-slate-900 peer-checked:shadow-sm peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500/40">
                                    {{ $label }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <x-button type="submit" icon="filter" class="w-full justify-center lg:w-auto">Terapkan</x-button>
            </form>

            <div class="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-3">
                <p class="text-xs text-slate-500">
                    Menampilkan <span class="font-semibold text-slate-700">{{ $customers->total() }}</span> pelanggan
                    @if ($search !== '')
                        untuk <span class="font-medium text-slate-700">“{{ $search }}”</span>
                    @endif
                    @if ($status === 'active')
                        dengan status <span class="font-medium text-slate-700">aktif</span>
                    @elseif ($status === 'inactive')
                        dengan status <span class="font-medium text-slate-700">nonaktif</span>
                    @endif
                </p>

                @if ($search !== '' || $status !== '')
                    <a href="{{ route('logistics.customers.index') }}" class="inline-flex items-center gap-1 text-xs font-medium text-brand-700 transition hover:text-brand-900">
                        <x-icon name="x" class="size-3.5" /> Reset filter
                    </a>
                @endif
            </div>
        </x-card>

        <x-table class="hidden md:block" padding="p-0">
            <x-slot:footer><x-pagination :paginator="$customers" class="!mt-0" /></x-slot:footer>

            <thead class="bg-slate-50/70">
                <tr>
                    <x-th>Pabrik / Alamat</x-th>
                    <x-th>Kontak penerima</x-th>
                    <x-th>Kota</x-th>
                    <x-th>Titik maps</x-th>
                    <x-th>Status</x-th>
                    <x-th align="right">Aksi</x-th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($customers as $customer)
                    <tr class="hover:bg-slate-50/60">
                        <x-td>
                            @if ($customer->kode_pelanggan)
                                <p class="font-mono text-[11px] font-medium text-brand-700">{{ $customer->kode_pelanggan }}</p>
                            @endif
                            <p class="font-medium text-slate-900">{{ $customer->nama_pabrik }}</p>
                            <p class="max-w-sm text-xs text-slate-500">{{ $customer->alamat }}</p>
                        </x-td>
                        <x-td>
                            <p class="font-medium text-slate-800">{{ $customer->kontak_nama }}</p>
                            @if ($customer->kontak_jabatan)
                                <p class="text-[11px] text-slate-500">{{ $customer->kontak_jabatan }}</p>
                            @endif
                            <p class="text-xs text-slate-500">{{ $customer->kontak_telepon }}</p>
                            @if ($customer->email)
                                <p class="text-[11px] text-slate-500">{{ $customer->email }}</p>
                            @endif
                        </x-td>
                        <x-td>
                            <p class="text-slate-700">{{ $customer->kota ?? '—' }}</p>
                            @if ($customer->provinsi)
                                <p class="text-[11px] text-slate-500">{{ $customer->provinsi }}</p>
                            @endif
                        </x-td>
                        <x-td>
                            @if ($customer->latitude !== null && $customer->longitude !== null)
                                <a href="https://maps.google.com/?q={{ $customer->latitude }},{{ $customer->longitude }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-brand-700 hover:text-brand-900">
                                    <x-icon name="map-pin" class="size-3.5" /> Buka peta
                                </a>
                            @else
                                <span class="text-slate-400">Belum diatur</span>
                            @endif
                        </x-td>
                        <x-td><x-badge :tone="$customer->is_active ? 'success' : 'danger'" dot>{{ $customer->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge></x-td>
                        <x-td align="right">
                            <div class="flex items-center justify-end gap-2">
                                <x-button href="{{ route('logistics.customers.edit', $customer) }}" variant="ghost" size="sm" icon="edit">Edit</x-button>
                                <x-confirm-button
                                    :action="route('logistics.customers.toggle-active', $customer)"
                                    method="PATCH"
                                    :title="$customer->is_active ? 'Nonaktifkan pelanggan?' : 'Aktifkan pelanggan?'"
                                    :description="'Status pelanggan '.$customer->nama_pabrik.' akan diubah.'"
                                    :confirm-label="$customer->is_active ? 'Ya, nonaktifkan' : 'Ya, aktifkan'"
                                    :tone="$customer->is_active ? 'danger' : 'brand'"
                                    variant="ghost"
                                    :icon="$customer->is_active ? 'ban' : 'check'"
                                >
                                    {{ $customer->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </x-confirm-button>
                            </div>
                        </x-td>
                    </tr>
                @empty
                    <tr><x-td colspan="6" class="py-8 text-center text-slate-500">Belum ada pelanggan yang cocok dengan filter.</x-td></tr>
                @endforelse
            </tbody>
        </x-table>

        <div class="space-y-3 md:hidden">
            @forelse ($customers as $customer)
                <x-card padding="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            @if ($customer->kode_pelanggan)
                                <p class="font-mono text-[11px] font-medium text-brand-700">{{ $customer->kode_pelanggan }}</p>
                            @endif
                            <p class="text-sm font-semibold text-slate-900">{{ $customer->nama_pabrik }}</p>
                            <p class="text-xs text-slate-500">{{ $customer->alamat }}</p>
                            @if ($customer->kota)
                                <p class="text-xs text-slate-500">{{ $customer->kota }}{{ $customer->provinsi ? ', '.$customer->provinsi : '' }}</p>
                            @endif
                        </div>
                        <x-badge :tone="$customer->is_active ? 'success' : 'danger'" dot>{{ $customer->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-slate-500">
                        <span class="inline-flex items-center gap-1"><x-icon name="phone" class="size-3.5" /> {{ $customer->kontak_nama }}{{ $customer->kontak_jabatan ? ' ('.$customer->kontak_jabatan.')' : '' }} · {{ $customer->kontak_telepon }}</span>
                        @if ($customer->email)
                            <span class="inline-flex items-center gap-1"><x-icon name="mail" class="size-3.5" /> {{ $customer->email }}</span>
                        @endif
                        @if ($customer->latitude !== null && $customer->longitude !== null)
                            <a href="https://maps.google.com/?q={{ $customer->latitude }},{{ $customer->longitude }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-brand-700">
                                <x-icon name="map-pin" class="size-3.5" /> Peta
                            </a>
                        @endif
                    </div>
                    <div class="mt-4 flex items-center justify-end gap-2 border-t border-slate-100 pt-3">
                        <x-button href="{{ route('logistics.customers.edit', $customer) }}" variant="secondary" size="sm">Edit</x-button>
                        <x-confirm-button
                            :action="route('logistics.customers.toggle-active', $customer)"
                            method="PATCH"
                            :title="$customer->is_active ? 'Nonaktifkan pelanggan?' : 'Aktifkan pelanggan?'"
                            :description="'Status pelanggan '.$customer->nama_pabrik.' akan diubah.'"
                            :confirm-label="$customer->is_active ? 'Ya, nonaktifkan' : 'Ya, aktifkan'"
                            :tone="$customer->is_active ? 'danger' : 'brand'"
                            variant="ghost"
                            :icon="$customer->is_active ? 'ban' : 'check'"
                        >
                            {{ $customer->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                        </x-confirm-button>
                    </div>
                </x-card>
            @empty
                <x-empty-state icon="building" title="Belum ada pelanggan" description="Tambahkan pabrik tujuan untuk mulai menerbitkan surat jalan." />
            @endforelse
            <x-pagination :paginator="$customers" />
        </div>
    </div>
@endsection
