@extends('layouts.app')

@section('title', 'Produksi')

@section('content')
    <div class="space-y-6">
        <x-page-header title="Produksi / Merge" description="Tentukan merge dan spesifikasi produksi. Hanya satu batch yang aktif untuk penimbangan bobbin.">
            <x-slot:actions>
                <x-button href="{{ route('warehouse.batches.create') }}" icon="plus">Tambah batch</x-button>
            </x-slot:actions>
        </x-page-header>

        @if ($activeBatch)
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                <div>
                    <p class="font-semibold">Batch aktif: {{ $activeBatch->nomor_merge }}</p>
                    <p class="mt-0.5 text-xs text-emerald-700">DTY {{ $activeBatch->specification }} · produksi {{ $activeBatch->production_date->translatedFormat('d M Y') }}</p>
                </div>
                <x-button href="{{ route('warehouse.rolls.create') }}" size="sm" icon="package">Timbang bobbin</x-button>
            </div>
        @else
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                Belum ada batch aktif. Aktifkan salah satu batch sebelum menimbang bobbin.
            </div>
        @endif

        <x-card padding="p-3 sm:p-4">
            <form method="GET" action="{{ route('warehouse.batches.index') }}" class="flex flex-col gap-3 sm:flex-row">
                <div class="relative min-w-0 flex-1">
                    <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                    <x-input id="q" name="q" type="search" value="{{ $search }}" placeholder="Cari merge, denier, atau filamen" aria-label="Cari batch produksi" class="pl-10" />
                </div>
                <x-button type="submit" icon="search">Cari</x-button>
                @if ($search !== '')
                    <x-button href="{{ route('warehouse.batches.index') }}" variant="ghost">Reset</x-button>
                @endif
            </form>
        </x-card>

        <x-table padding="p-0">
            <x-slot:footer><x-pagination :paginator="$batches" class="!mt-0" /></x-slot:footer>

            <thead class="bg-slate-50/70">
                <tr>
                    <x-th>Merge / Lot</x-th>
                    <x-th>Spesifikasi</x-th>
                    <x-th>Tanggal produksi</x-th>
                    <x-th>Bobbin</x-th>
                    <x-th>Status</x-th>
                    <x-th align="right">Aksi</x-th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($batches as $batch)
                    <tr class="hover:bg-slate-50/60">
                        <x-td><span class="font-mono text-xs font-semibold text-slate-900">{{ $batch->nomor_merge }}</span></x-td>
                        <x-td class="tabular-nums">DTY {{ $batch->specification }}</x-td>
                        <x-td>{{ $batch->production_date->translatedFormat('d M Y') }}</x-td>
                        <x-td class="tabular-nums">{{ $batch->rolls_count }}</x-td>
                        <x-td>
                            <x-badge :tone="$batch->is_active ? 'success' : 'neutral'" dot>{{ $batch->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge>
                        </x-td>
                        <x-td align="right">
                            <div class="flex flex-wrap items-center justify-end gap-2">
                                @if (! $batch->is_active)
                                    <x-confirm-button
                                        :action="route('warehouse.batches.activate', $batch)"
                                        method="PATCH"
                                        title="Aktifkan batch produksi?"
                                        :description="'Batch '.$batch->nomor_merge.' akan menjadi acuan penimbangan bobbin.'"
                                        note="Batch aktif lain akan dinonaktifkan. Bobbin yang sudah ditimbang tidak berubah."
                                        confirm-label="Ya, aktifkan"
                                        tone="brand"
                                        variant="ghost"
                                        icon="check"
                                    >
                                        Aktifkan
                                    </x-confirm-button>
                                @endif
                                <x-button href="{{ route('warehouse.batches.edit', $batch) }}" variant="ghost" size="sm" icon="edit">Edit</x-button>
                            </div>
                        </x-td>
                    </tr>
                @empty
                    <tr><x-td colspan="6" class="py-8 text-center text-slate-500">Belum ada batch produksi.</x-td></tr>
                @endforelse
            </tbody>
        </x-table>
    </div>
@endsection
