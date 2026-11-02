@extends('layouts.app')

@section('title', 'Gulungan DTY')

@section('content')
    <div class="space-y-6">
        <x-page-header title="Stok gulungan DTY" description="Catat spesifikasi, nomor lot, dan cetak label QR untuk tiap gulungan.">
            <x-slot:actions>
                <x-button href="{{ route('warehouse.rolls.create') }}" icon="plus">Tambah gulungan</x-button>
            </x-slot:actions>
        </x-page-header>

        <x-card padding="p-4">
            <form method="GET" action="{{ route('warehouse.rolls.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1.6fr_1fr_auto]">
                <x-field name="q">
                    <x-input id="q" name="q" type="search" value="{{ $search }}" placeholder="Cari kode rol atau nomor lot" aria-label="Cari gulungan" />
                </x-field>

                <x-field name="status">
                    <x-select id="status" name="status" aria-label="Filter status">
                        <option value="">Semua status</option>
                        @foreach ($statuses as $statusOption)
                            <option value="{{ $statusOption->value }}" @selected($status === $statusOption->value)>{{ $statusOption->label() }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <div class="flex items-end gap-2">
                    <x-button type="submit" variant="secondary" icon="filter">Filter</x-button>
                    <x-button href="{{ route('warehouse.rolls.index') }}" variant="ghost">Reset</x-button>
                </div>
            </form>
        </x-card>

        <x-table class="hidden md:block" padding="p-0">
            <x-slot:footer><x-pagination :paginator="$rolls" class="!mt-0" /></x-slot:footer>

            <thead class="bg-slate-50/70">
                <tr>
                    <x-th>Kode rol / Lot</x-th>
                    <x-th>Spesifikasi</x-th>
                    <x-th>Berat</x-th>
                    <x-th>Status</x-th>
                    <x-th align="right">Aksi</x-th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($rolls as $roll)
                    <tr class="hover:bg-slate-50/60">
                        <x-td>
                            <p class="font-mono text-xs font-semibold text-slate-800">{{ $roll->kode_rol }}</p>
                            <p class="text-xs text-slate-500">Lot {{ $roll->nomor_lot }}</p>
                        </x-td>
                        <x-td class="tabular-nums">{{ number_format($roll->denier) }} D / {{ number_format($roll->filamen) }} F</x-td>
                        <x-td class="tabular-nums">{{ number_format((float) $roll->berat_gram, 2, ',', '.') }} g</x-td>
                        <x-td><x-status-badge :status="$roll->status" /></x-td>
                        <x-td align="right">
                            <div class="flex items-center justify-end gap-2">
                                @if ($roll->status !== \App\Enums\RollStatus::Cancelled)
                                    <x-button href="{{ route('warehouse.rolls.label', $roll) }}" variant="ghost" size="sm" icon="printer">Label</x-button>
                                @endif
                                @if ($roll->status === \App\Enums\RollStatus::InStock)
                                    <x-button href="{{ route('warehouse.rolls.edit', $roll) }}" variant="ghost" size="sm" icon="edit">Edit</x-button>
                                    <x-confirm-button
                                        :action="route('warehouse.rolls.cancel', $roll)"
                                        method="PATCH"
                                        title="Batalkan gulungan?"
                                        :description="'Gulungan '.$roll->kode_rol.' akan ditandai batal. Riwayat tetap disimpan.'"
                                        confirm-label="Ya, batalkan"
                                        variant="ghost"
                                        icon="ban"
                                    >
                                        Batalkan
                                    </x-confirm-button>
                                @endif
                            </div>
                        </x-td>
                    </tr>
                @empty
                    <tr><x-td colspan="5" class="py-8 text-center text-slate-500">Belum ada gulungan yang cocok dengan filter.</x-td></tr>
                @endforelse
            </tbody>
        </x-table>

        <div class="space-y-3 md:hidden">
            @forelse ($rolls as $roll)
                <x-card padding="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-mono text-xs font-semibold text-slate-800">{{ $roll->kode_rol }}</p>
                            <p class="text-xs text-slate-500">Lot {{ $roll->nomor_lot }}</p>
                        </div>
                        <x-status-badge :status="$roll->status" />
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-slate-500">
                        <p>Spesifikasi: <span class="font-medium text-slate-700">{{ $roll->denier }}D / {{ $roll->filamen }}F</span></p>
                        <p>Berat: <span class="font-medium text-slate-700">{{ number_format((float) $roll->berat_gram, 2, ',', '.') }} g</span></p>
                    </div>
                    <div class="mt-4 flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 pt-3">
                        @if ($roll->status !== \App\Enums\RollStatus::Cancelled)
                            <x-button href="{{ route('warehouse.rolls.label', $roll) }}" variant="secondary" size="sm">Label</x-button>
                        @endif
                        @if ($roll->status === \App\Enums\RollStatus::InStock)
                            <x-button href="{{ route('warehouse.rolls.edit', $roll) }}" variant="secondary" size="sm">Edit</x-button>
                            <x-confirm-button
                                :action="route('warehouse.rolls.cancel', $roll)"
                                method="PATCH"
                                title="Batalkan gulungan?"
                                :description="'Gulungan '.$roll->kode_rol.' akan ditandai batal. Riwayat tetap disimpan.'"
                                confirm-label="Ya, batalkan"
                                variant="ghost"
                                icon="ban"
                            >
                                Batalkan
                            </x-confirm-button>
                        @endif
                    </div>
                </x-card>
            @empty
                <x-empty-state icon="package" title="Belum ada gulungan" description="Tambahkan gulungan DTY untuk mulai mencetak label QR." />
            @endforelse
            <x-pagination :paginator="$rolls" />
        </div>
    </div>
@endsection
