@extends('layouts.app')

@section('title', 'Armada')

@section('content')
    <div class="space-y-6">
        <x-page-header title="Master armada" description="Kelola kendaraan, kapasitas, dan pengemudi utama.">
            <x-slot:actions>
                <x-button href="{{ route('logistics.fleets.create') }}" icon="plus">Tambah armada</x-button>
            </x-slot:actions>
        </x-page-header>

        <x-card padding="p-4">
            <form method="GET" action="{{ route('logistics.fleets.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1.6fr_1fr_auto]">
                <x-field name="q">
                    <x-input id="q" name="q" type="search" value="{{ $search }}" placeholder="Cari nomor polisi, kendaraan, pengemudi" aria-label="Cari armada" />
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
                    <x-button href="{{ route('logistics.fleets.index') }}" variant="ghost">Reset</x-button>
                </div>
            </form>
        </x-card>

        <x-table class="hidden md:block" padding="p-0">
            <x-slot:footer><x-pagination :paginator="$fleets" class="!mt-0" /></x-slot:footer>

            <thead class="bg-slate-50/70">
                <tr>
                    <x-th>Nomor polisi</x-th>
                    <x-th>Jenis kendaraan</x-th>
                    <x-th>Kapasitas</x-th>
                    <x-th>Pengemudi</x-th>
                    <x-th>Status</x-th>
                    <x-th align="right">Aksi</x-th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($fleets as $fleet)
                    <tr class="hover:bg-slate-50/60">
                        <x-td><span class="font-mono text-xs font-semibold text-slate-900">{{ $fleet->nomor_polisi }}</span></x-td>
                        <x-td>{{ $fleet->jenis_kendaraan }}</x-td>
                        <x-td class="tabular-nums">{{ number_format((float) $fleet->kapasitas_kg, 2, ',', '.') }} kg</x-td>
                        <x-td>{{ $fleet->driver?->nama ?? 'Belum ditentukan' }}</x-td>
                        <x-td><x-badge :tone="$fleet->is_active ? 'success' : 'danger'" dot>{{ $fleet->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge></x-td>
                        <x-td align="right">
                            <div class="flex items-center justify-end gap-2">
                                <x-button href="{{ route('logistics.fleets.edit', $fleet) }}" variant="ghost" size="sm" icon="edit">Edit</x-button>
                                <x-confirm-button
                                    :action="route('logistics.fleets.toggle-active', $fleet)"
                                    method="PATCH"
                                    :title="$fleet->is_active ? 'Nonaktifkan armada?' : 'Aktifkan armada?'"
                                    :description="'Status armada '.$fleet->nomor_polisi.' akan diubah.'"
                                    :confirm-label="$fleet->is_active ? 'Ya, nonaktifkan' : 'Ya, aktifkan'"
                                    :tone="$fleet->is_active ? 'danger' : 'brand'"
                                    variant="ghost"
                                    :icon="$fleet->is_active ? 'ban' : 'check'"
                                >
                                    {{ $fleet->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </x-confirm-button>
                            </div>
                        </x-td>
                    </tr>
                @empty
                    <tr><x-td colspan="6" class="py-8 text-center text-slate-500">Belum ada armada yang cocok dengan filter.</x-td></tr>
                @endforelse
            </tbody>
        </x-table>

        <div class="space-y-3 md:hidden">
            @forelse ($fleets as $fleet)
                <x-card padding="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-mono text-sm font-semibold text-slate-900">{{ $fleet->nomor_polisi }}</p>
                            <p class="text-xs text-slate-500">{{ $fleet->jenis_kendaraan }}</p>
                        </div>
                        <x-badge :tone="$fleet->is_active ? 'success' : 'danger'" dot>{{ $fleet->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge>
                    </div>
                    <dl class="mt-3 grid grid-cols-2 gap-2 text-xs">
                        <div><dt class="text-slate-500">Kapasitas</dt><dd class="font-medium tabular-nums text-slate-800">{{ number_format((float) $fleet->kapasitas_kg, 2, ',', '.') }} kg</dd></div>
                        <div><dt class="text-slate-500">Pengemudi</dt><dd class="font-medium text-slate-800">{{ $fleet->driver?->nama ?? 'Belum ditentukan' }}</dd></div>
                    </dl>
                    <div class="mt-4 flex items-center justify-end gap-2 border-t border-slate-100 pt-3">
                        <x-button href="{{ route('logistics.fleets.edit', $fleet) }}" variant="secondary" size="sm">Edit</x-button>
                        <x-confirm-button
                            :action="route('logistics.fleets.toggle-active', $fleet)"
                            method="PATCH"
                            :title="$fleet->is_active ? 'Nonaktifkan armada?' : 'Aktifkan armada?'"
                            :description="'Status armada '.$fleet->nomor_polisi.' akan diubah.'"
                            :confirm-label="$fleet->is_active ? 'Ya, nonaktifkan' : 'Ya, aktifkan'"
                            :tone="$fleet->is_active ? 'danger' : 'brand'"
                            variant="ghost"
                            :icon="$fleet->is_active ? 'ban' : 'check'"
                        >
                            {{ $fleet->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                        </x-confirm-button>
                    </div>
                </x-card>
            @empty
                <x-empty-state icon="truck" title="Belum ada armada" description="Tambahkan kendaraan untuk mulai menerbitkan surat jalan." />
            @endforelse
            <x-pagination :paginator="$fleets" />
        </div>
    </div>
@endsection
