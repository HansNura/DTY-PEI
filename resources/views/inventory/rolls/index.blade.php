@extends('layouts.app')

@section('title', 'Gulungan DTY')

@section('content')
    <div class="space-y-6">
        <x-page-header title="Stok bobbin DTY" description="Daftar bobbin hasil input kardus atau input manual, lengkap dengan QR untuk validasi muatan.">
            <x-slot:actions>
                <x-button href="{{ route('warehouse.cartons.index') }}" variant="secondary" icon="boxes">Kardus DTY</x-button>
                <x-button href="{{ route('warehouse.rolls.create') }}" icon="plus">Tambah bobbin</x-button>
            </x-slot:actions>
        </x-page-header>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <x-stat-card label="Bobbin tersedia" :value="number_format($summary['in_stock'], 0, ',', '.')" icon="package" tone="success" hint="Siap dialokasikan" />
            <x-stat-card label="Dialokasikan" :value="number_format($summary['allocated'], 0, ',', '.')" icon="clipboard" tone="warning" hint="Menunggu dimuat" />
            <x-stat-card label="Dimuat" :value="number_format($summary['loaded'], 0, ',', '.')" icon="truck" tone="info" hint="Sedang dalam pengiriman" />
            <x-stat-card label="Berat tersedia" :value="number_format($summary['available_weight_kg'], 2, ',', '.').' kg'" icon="package" tone="brand" hint="Total stok siap kirim" />
        </div>

        <x-card padding="p-3 sm:p-4">
            <form method="GET" action="{{ route('warehouse.rolls.index') }}" class="space-y-3">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative min-w-0 flex-1">
                        <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                        <x-input id="q" name="q" type="search" value="{{ $search }}" placeholder="Cari kode rol, serial, lot, nomor HU, atau merge" aria-label="Cari gulungan" class="pl-10" />
                    </div>

                    <x-button type="submit" icon="search" class="w-full justify-center sm:w-auto">Cari</x-button>
                </div>

                <fieldset class="flex flex-wrap items-center gap-2">
                    <legend class="sr-only">Filter status bobbin</legend>
                    @php
                        $statusFilters = [
                            ['value' => '', 'label' => 'Semua', 'count' => $summary['total'], 'dot' => 'bg-slate-400'],
                            ['value' => \App\Enums\RollStatus::InStock->value, 'label' => 'Tersedia', 'count' => $summary['in_stock'], 'dot' => 'bg-emerald-500'],
                            ['value' => \App\Enums\RollStatus::Allocated->value, 'label' => 'Dialokasikan', 'count' => $summary['allocated'], 'dot' => 'bg-amber-500'],
                            ['value' => \App\Enums\RollStatus::Loaded->value, 'label' => 'Dimuat', 'count' => $summary['loaded'], 'dot' => 'bg-sky-500'],
                            ['value' => \App\Enums\RollStatus::Delivered->value, 'label' => 'Terkirim', 'count' => $summary['delivered'], 'dot' => 'bg-teal-500'],
                            ['value' => \App\Enums\RollStatus::Cancelled->value, 'label' => 'Dibatalkan', 'count' => $summary['cancelled'], 'dot' => 'bg-rose-500'],
                        ];
                    @endphp

                    @foreach ($statusFilters as $filter)
                        <label class="cursor-pointer select-none">
                            <input type="radio" name="status" value="{{ $filter['value'] }}" class="peer sr-only" @checked($status === $filter['value']) onchange="this.form.requestSubmit()" />
                            <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 peer-checked:border-brand-200 peer-checked:bg-brand-50 peer-checked:text-brand-700 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500/40">
                                <span class="size-1.5 rounded-full {{ $filter['dot'] }}"></span>
                                {{ $filter['label'] }}
                                <span class="rounded-full bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold tabular-nums text-slate-500">{{ number_format($filter['count'], 0, ',', '.') }}</span>
                            </span>
                        </label>
                    @endforeach
                </fieldset>
            </form>

            <div class="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-3">
                <p class="text-xs text-slate-500">
                    Menampilkan <span class="font-semibold text-slate-700">{{ $rolls->total() }}</span> bobbin
                    @if ($search !== '')
                        untuk <span class="font-medium text-slate-700">“{{ $search }}”</span>
                    @endif
                    @if ($statusLabel)
                        dengan status <span class="font-medium text-slate-700">{{ strtolower($statusLabel) }}</span>
                    @endif
                </p>

                @if ($search !== '' || $status !== '')
                    <a href="{{ route('warehouse.rolls.index') }}" class="inline-flex items-center gap-1 text-xs font-medium text-brand-700 transition hover:text-brand-900">
                        <x-icon name="x" class="size-3.5" /> Reset filter
                    </a>
                @endif
            </div>
        </x-card>

        <x-table class="hidden md:block" padding="p-0">
            <x-slot:footer><x-pagination :paginator="$rolls" class="!mt-0" /></x-slot:footer>

            <thead class="bg-slate-50/70">
                <tr>
                    <x-th>Kode rol / Serial</x-th>
                    <x-th>Kardus</x-th>
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
                            @if ($roll->nomor_seri)
                                <p class="font-mono text-[10px] text-slate-500">{{ $roll->nomor_seri }}</p>
                            @endif
                            <p class="text-xs text-slate-500">Lot {{ $roll->nomor_lot }}</p>
                        </x-td>
                        <x-td>
                            @if ($roll->carton)
                                <a href="{{ route('warehouse.cartons.label', $roll->carton) }}" class="font-mono text-xs font-medium text-brand-700 hover:text-brand-900">{{ $roll->carton->nomor_hu }}</a>
                                <p class="text-[11px] text-slate-500">Merge {{ $roll->carton->nomor_merge }}</p>
                            @else
                                <span class="text-xs text-slate-400">Input manual</span>
                            @endif
                        </x-td>
                        <x-td class="tabular-nums">{{ number_format($roll->denier) }} D / {{ number_format($roll->filamen) }} F</x-td>
                        <x-td class="tabular-nums">{{ number_format((float) $roll->berat_gram, 2, ',', '.') }} g</x-td>
                        <x-td>
                            <div class="space-y-1">
                                <x-status-badge :status="$roll->status" />
                                <p class="text-[10px] text-slate-500">Bobot {{ $roll->bobbin_weight_grade ?? '—' }}</p>
                            </div>
                        </x-td>
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
                                        note="Gulungan tidak dapat dipakai di surat jalan setelah dibatalkan. Data dan riwayat scan tetap tersimpan."
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
                    <tr><x-td colspan="6" class="py-8 text-center text-slate-500">Belum ada gulungan yang cocok dengan filter.</x-td></tr>
                @endforelse
            </tbody>
        </x-table>

        <div class="space-y-3 md:hidden">
            @forelse ($rolls as $roll)
                <x-card padding="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-mono text-xs font-semibold text-slate-800">{{ $roll->kode_rol }}</p>
                            @if ($roll->nomor_seri)
                                <p class="font-mono text-[10px] text-slate-500">{{ $roll->nomor_seri }}</p>
                            @endif
                            <p class="text-xs text-slate-500">Lot {{ $roll->nomor_lot }}</p>
                        </div>
                        <div class="text-right">
                            <x-status-badge :status="$roll->status" />
                            <p class="mt-1 text-[10px] text-slate-500">Bobot {{ $roll->bobbin_weight_grade ?? '—' }}</p>
                        </div>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-slate-500">
                        <p>Spesifikasi: <span class="font-medium text-slate-700">{{ $roll->denier }}D / {{ $roll->filamen }}F</span></p>
                        <p>Berat: <span class="font-medium text-slate-700">{{ number_format((float) $roll->berat_gram, 2, ',', '.') }} g</span></p>
                        <p class="col-span-2">Kardus: <span class="font-mono font-medium text-slate-700">{{ $roll->carton?->nomor_hu ?? 'Input manual' }}</span></p>
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
                                note="Gulungan tidak dapat dipakai di surat jalan setelah dibatalkan. Data dan riwayat scan tetap tersimpan."
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
