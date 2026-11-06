@extends('layouts.app')

@section('title', 'Armada')

@section('content')
    <div class="space-y-6">
        <x-page-header title="Master armada" description="Kelola kendaraan, kapasitas, dan pengemudi utama.">
            <x-slot:actions>
                <x-button href="{{ route('logistics.fleets.create') }}" icon="plus">Tambah armada</x-button>
            </x-slot:actions>
        </x-page-header>

        <x-card padding="p-3 sm:p-4">
            <form method="GET" action="{{ route('logistics.fleets.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-center">
                <div class="relative min-w-0 flex-1">
                    <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                    <x-input id="q" name="q" type="search" value="{{ $search }}" placeholder="Cari nomor polisi, jenis kendaraan, atau pengemudi" aria-label="Cari armada" class="pl-10" />
                </div>

                <fieldset class="w-fit shrink-0 rounded-xl bg-slate-100 p-1">
                    <legend class="sr-only">Status armada</legend>
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
                    Menampilkan <span class="font-semibold text-slate-700">{{ $fleets->total() }}</span> armada
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
                    <a href="{{ route('logistics.fleets.index') }}" class="inline-flex items-center gap-1 text-xs font-medium text-brand-700 transition hover:text-brand-900">
                        <x-icon name="x" class="size-3.5" /> Reset filter
                    </a>
                @endif
            </div>
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
                        <x-td>
                            @if ($fleet->kode_armada)
                                <p class="font-mono text-[11px] font-medium text-brand-700">{{ $fleet->kode_armada }}</p>
                            @endif
                            <span class="font-mono text-xs font-semibold text-slate-900">{{ $fleet->nomor_polisi }}</span>
                        </x-td>
                        <x-td>{{ $fleet->jenis_kendaraan }}</x-td>
                        <x-td class="tabular-nums">{{ number_format((float) $fleet->kapasitas_kg, 2, ',', '.') }} kg</x-td>
                        <x-td>{{ $fleet->driver?->nama ?? 'Belum ditentukan' }}</x-td>
                        <x-td><x-badge :tone="$fleet->is_active ? 'success' : 'danger'" dot>{{ $fleet->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge></x-td>
                        <x-td align="right">
                            <div class="flex flex-wrap items-center justify-end gap-2">
                                <x-fleet-drawer :fleet="$fleet" />
                                <x-button href="{{ route('logistics.fleets.edit', $fleet) }}" variant="ghost" size="sm" icon="edit">Edit</x-button>
                                <x-confirm-button
                                    :action="route('logistics.fleets.toggle-active', $fleet)"
                                    method="PATCH"
                                    :title="$fleet->is_active ? 'Nonaktifkan armada?' : 'Aktifkan armada?'"
                                    :description="'Status armada '.$fleet->nomor_polisi.' akan diubah.'"
                                    :note="$fleet->is_active
                                        ? 'Armada tidak akan muncul sebagai pilihan saat membuat surat jalan baru. Riwayat pengiriman tetap tersimpan.'
                                        : 'Armada akan kembali muncul sebagai pilihan saat membuat surat jalan baru.'"
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
                            @if ($fleet->kode_armada)
                                <p class="font-mono text-[11px] font-medium text-brand-700">{{ $fleet->kode_armada }}</p>
                            @endif
                            <p class="font-mono text-sm font-semibold text-slate-900">{{ $fleet->nomor_polisi }}</p>
                            <p class="text-xs text-slate-500">{{ $fleet->jenis_kendaraan }}</p>
                        </div>
                        <x-badge :tone="$fleet->is_active ? 'success' : 'danger'" dot>{{ $fleet->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge>
                    </div>
                    <dl class="mt-3 grid grid-cols-2 gap-2 text-xs">
                        <div><dt class="text-slate-500">Kapasitas</dt><dd class="font-medium tabular-nums text-slate-800">{{ number_format((float) $fleet->kapasitas_kg, 2, ',', '.') }} kg</dd></div>
                        <div><dt class="text-slate-500">Pengemudi</dt><dd class="font-medium text-slate-800">{{ $fleet->driver?->nama ?? 'Belum ditentukan' }}</dd></div>
                    </dl>
                    <div class="mt-4 flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 pt-3">
                        <x-fleet-drawer :fleet="$fleet" />
                        <x-button href="{{ route('logistics.fleets.edit', $fleet) }}" variant="secondary" size="sm">Edit</x-button>
                        <x-confirm-button
                            :action="route('logistics.fleets.toggle-active', $fleet)"
                            method="PATCH"
                            :title="$fleet->is_active ? 'Nonaktifkan armada?' : 'Aktifkan armada?'"
                            :description="'Status armada '.$fleet->nomor_polisi.' akan diubah.'"
                            :note="$fleet->is_active
                                ? 'Armada tidak akan muncul sebagai pilihan saat membuat surat jalan baru. Riwayat pengiriman tetap tersimpan.'
                                : 'Armada akan kembali muncul sebagai pilihan saat membuat surat jalan baru.'"
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
