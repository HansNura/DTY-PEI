@extends('layouts.app')

@section('title', 'Kardus DTY')

@section('content')
    <div class="space-y-6">
        <x-page-header title="Kardus DTY" description="Input per kardus: nomor HU, lot/merge, spesifikasi, berat, dan jumlah bobbin.">
            <x-slot:actions>
                <x-button href="{{ route('warehouse.rolls.index') }}" variant="secondary" icon="package">Stok bobbin</x-button>
                <x-button href="{{ route('warehouse.cartons.create') }}" icon="plus">Tambah kardus</x-button>
            </x-slot:actions>
        </x-page-header>

        <x-card padding="p-4">
            <form method="GET" action="{{ route('warehouse.cartons.index') }}" class="flex flex-col gap-3 sm:flex-row">
                <x-input id="q" name="q" type="search" value="{{ $search }}" placeholder="Cari nomor HU, lot/merge, grade, atau pallet" aria-label="Cari kardus" class="flex-1" />
                <x-button type="submit" variant="secondary" icon="search">Cari</x-button>
                <x-button href="{{ route('warehouse.cartons.index') }}" variant="ghost">Reset</x-button>
            </form>
        </x-card>

        <x-table class="hidden md:block" padding="p-0">
            <x-slot:footer><x-pagination :paginator="$cartons" class="!mt-0" /></x-slot:footer>

            <thead class="bg-slate-50/70">
                <tr>
                    <x-th>HU / Lot</x-th>
                    <x-th>Spesifikasi</x-th>
                    <x-th>Grade</x-th>
                    <x-th>Produksi</x-th>
                    <x-th>Berat (net / gross)</x-th>
                    <x-th>Bobbin</x-th>
                    <x-th>Status</x-th>
                    <x-th align="right">Aksi</x-th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($cartons as $carton)
                    <tr class="hover:bg-slate-50/60">
                        <x-td>
                            <p class="font-mono text-xs font-semibold text-slate-900">{{ $carton->nomor_hu }}</p>
                            <p class="text-xs text-slate-500">Merge {{ $carton->nomor_merge }}</p>
                            @if ($carton->pallet_number)
                                <p class="text-[11px] text-slate-400">Pallet {{ $carton->pallet_number }}</p>
                            @endif
                        </x-td>
                        <x-td class="tabular-nums">{{ $carton->specification }}</x-td>
                        <x-td><x-badge tone="brand">{{ $carton->grade }}</x-badge></x-td>
                        <x-td>{{ $carton->production_date->translatedFormat('d M Y') }}</x-td>
                        <x-td class="tabular-nums">{{ number_format((float) $carton->net_weight_kg, 2, ',', '.') }} / {{ number_format((float) $carton->gross_weight_kg, 2, ',', '.') }} kg</x-td>
                        <x-td class="tabular-nums">{{ $carton->rolls->count() }} / {{ $carton->unit_bobbins }}</x-td>
                        <x-td><x-status-badge :status="$carton->status" /></x-td>
                        <x-td align="right">
                            <div class="flex items-center justify-end gap-2">
                                <x-button href="{{ route('warehouse.cartons.label', $carton) }}" variant="ghost" size="sm" icon="printer">Label</x-button>
                                @if ($carton->status === \App\Enums\RollStatus::InStock)
                                    <x-button href="{{ route('warehouse.cartons.edit', $carton) }}" variant="ghost" size="sm" icon="edit">Edit</x-button>
                                @endif
                            </div>
                        </x-td>
                    </tr>
                @empty
                    <tr><x-td colspan="8" class="py-8 text-center text-slate-500">Belum ada kardus DTY yang cocok.</x-td></tr>
                @endforelse
            </tbody>
        </x-table>

        <div class="space-y-3 md:hidden">
            @forelse ($cartons as $carton)
                <x-card padding="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-mono text-xs font-semibold text-slate-900">{{ $carton->nomor_hu }}</p>
                            <p class="text-xs text-slate-500">Merge {{ $carton->nomor_merge }} · {{ $carton->grade }}</p>
                        </div>
                        <x-status-badge :status="$carton->status" />
                    </div>
                    <dl class="mt-3 grid grid-cols-2 gap-2 text-xs">
                        <div><dt class="text-slate-500">Spesifikasi</dt><dd class="font-medium text-slate-800">{{ $carton->specification }}</dd></div>
                        <div><dt class="text-slate-500">Produksi</dt><dd class="font-medium text-slate-800">{{ $carton->production_date->translatedFormat('d M Y') }}</dd></div>
                        <div><dt class="text-slate-500">Net / gross</dt><dd class="font-medium tabular-nums text-slate-800">{{ number_format((float) $carton->net_weight_kg, 2, ',', '.') }} / {{ number_format((float) $carton->gross_weight_kg, 2, ',', '.') }} kg</dd></div>
                        <div><dt class="text-slate-500">Bobbin</dt><dd class="font-medium tabular-nums text-slate-800">{{ $carton->rolls->count() }} / {{ $carton->unit_bobbins }}</dd></div>
                    </dl>
                    <div class="mt-4 flex items-center justify-end gap-2 border-t border-slate-100 pt-3">
                        <x-button href="{{ route('warehouse.cartons.label', $carton) }}" variant="secondary" size="sm" icon="printer">Label</x-button>
                        @if ($carton->status === \App\Enums\RollStatus::InStock)
                            <x-button href="{{ route('warehouse.cartons.edit', $carton) }}" variant="secondary" size="sm" icon="edit">Edit</x-button>
                        @endif
                    </div>
                </x-card>
            @empty
                <x-empty-state icon="boxes" title="Belum ada kardus" description="Tambahkan kardus DTY pertama untuk membuat bobbin dan QR secara otomatis." />
            @endforelse
            <x-pagination :paginator="$cartons" />
        </div>
    </div>
@endsection
