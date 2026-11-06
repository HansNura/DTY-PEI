@extends('layouts.app')

@section('title', 'Stok Bobbin')

@section('content')
    <div class="space-y-6">
        <x-page-header title="Stok bobbin DTY" description="Bobbin siap packing dan stok yang sudah masuk kardus, lengkap dengan serial dan QR.">
            <x-slot:actions>
                <x-button href="{{ route('warehouse.cartons.index') }}" variant="secondary" icon="boxes">Kardus DTY</x-button>
                <x-button href="{{ route('warehouse.rolls.create') }}" icon="plus">Timbang bobbin</x-button>
            </x-slot:actions>
        </x-page-header>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <x-stat-card label="Siap packing" :value="number_format($summary['ready_to_pack'], 0, ',', '.')" icon="package" tone="warning" hint="Belum masuk kardus" />
            <x-stat-card label="Tersedia" :value="number_format($summary['in_stock'], 0, ',', '.')" icon="package" tone="success" hint="Sudah dipacking" />
            <x-stat-card label="Dialokasikan" :value="number_format($summary['allocated'], 0, ',', '.')" icon="clipboard" tone="info" hint="Menunggu dimuat" />
            <x-stat-card label="Berat siap kirim" :value="number_format($summary['available_weight_kg'], 2, ',', '.').' kg'" icon="download" tone="brand" hint="Siap packing + tersedia" />
        </div>

        @if (($summary['overweight'] + $summary['waste']) > 0)
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <div>
                    <p class="font-semibold">Anomali bobot perlu tindakan</p>
                    <p class="mt-0.5 text-xs text-amber-800">{{ $summary['overweight'] }} Overweight / Hold · {{ $summary['waste'] }} Waste / Scrap. Bobbin anomali tidak dapat dipacking ke box reguler.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if ($summary['overweight'] > 0)
                        <x-button href="{{ route('warehouse.rolls.index', ['status' => \App\Enums\RollStatus::Overweight->value]) }}" variant="secondary" size="sm">Overweight · {{ $summary['overweight'] }}</x-button>
                    @endif
                    @if ($summary['waste'] > 0)
                        <x-button href="{{ route('warehouse.rolls.index', ['status' => \App\Enums\RollStatus::Waste->value]) }}" variant="secondary" size="sm">Waste · {{ $summary['waste'] }}</x-button>
                    @endif
                </div>
            </div>
        @endif

        <x-card padding="p-3 sm:p-4">
            <form method="GET" action="{{ route('warehouse.rolls.index') }}" class="space-y-3">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative min-w-0 flex-1">
                        <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                        <x-input id="q" name="q" type="search" value="{{ $search }}" placeholder="Cari nomor seri, lot, nomor HU, atau merge" aria-label="Cari gulungan" class="pl-10" />
                    </div>

                    <x-button type="submit" icon="search" class="w-full justify-center sm:w-auto">Cari</x-button>
                </div>

                <fieldset class="flex flex-wrap items-center gap-2">
                    <legend class="sr-only">Filter status bobbin</legend>
                    @php
                        $statusFilters = [
                            ['value' => '', 'label' => 'Semua', 'count' => $summary['total'], 'dot' => 'bg-slate-400'],
                            ['value' => \App\Enums\RollStatus::ReadyToPack->value, 'label' => 'Siap packing', 'count' => $summary['ready_to_pack'], 'dot' => 'bg-indigo-500'],
                            ['value' => \App\Enums\RollStatus::Overweight->value, 'label' => 'Overweight', 'count' => $summary['overweight'], 'dot' => 'bg-orange-500'],
                            ['value' => \App\Enums\RollStatus::Waste->value, 'label' => 'Waste', 'count' => $summary['waste'], 'dot' => 'bg-red-600'],
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

            <thead class="bg-slate-50/80">
                <tr>
                    <x-th class="pl-6">Bobbin</x-th>
                    <x-th>Status &amp; Grade</x-th>
                    <x-th>Kardus / Batch</x-th>
                    <x-th>Spesifikasi</x-th>
                    <x-th align="right">Berat</x-th>
                    <x-th align="right" class="pr-6">Aksi</x-th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($rolls as $roll)
                    @php
                        $grade = $roll->bobbin_weight_grade;
                        $gradeTone = match ($grade) {
                            'AX' => 'success',
                            'AE' => 'info',
                            'A' => 'warning',
                            'B' => 'neutral',
                            'OW' => 'warning',
                            'WASTE' => 'danger',
                            default => 'neutral',
                        };
                        $isAnomaly = in_array($roll->status, [\App\Enums\RollStatus::Overweight, \App\Enums\RollStatus::Waste], true);
                        $rowTone = $roll->status === \App\Enums\RollStatus::Waste
                            ? 'bg-rose-50/40'
                            : ($roll->status === \App\Enums\RollStatus::Overweight ? 'bg-amber-50/40' : '');
                    @endphp

                    <tr class="{{ $rowTone }} transition hover:bg-slate-50/70">
                        <x-td class="pl-6">
                            <div class="flex items-start gap-2">
                                @if ($isAnomaly)
                                    <x-icon name="alert" class="mt-0.5 size-3.5 shrink-0 text-amber-600" />
                                @endif
                                <div class="min-w-0">
                                    <p class="font-mono text-[13px] font-semibold tracking-wide text-slate-900">{{ $roll->nomor_seri ?? '—' }}</p>
                                    <p class="mt-0.5 text-[11px] text-slate-500">Lot <span class="font-medium text-slate-600">{{ $roll->nomor_lot }}</span></p>
                                </div>
                            </div>
                        </x-td>
                        <x-td>
                            <div class="flex flex-wrap items-center gap-1.5">
                                <x-status-badge :status="$roll->status" />
                                <x-badge :tone="$gradeTone">Grade {{ $grade ?? '—' }}</x-badge>
                            </div>
                        </x-td>
                        <x-td>
                            @if ($roll->carton)
                                <a href="{{ route('warehouse.cartons.label', $roll->carton) }}" class="inline-flex items-center gap-1.5 font-mono text-xs font-semibold text-brand-700 transition hover:text-brand-900">
                                    <x-icon name="boxes" class="size-3.5" />
                                    {{ $roll->carton->nomor_hu }}
                                </a>
                                <p class="mt-0.5 text-[11px] text-slate-500">Merge {{ $roll->carton->nomor_merge }}</p>
                            @elseif ($roll->productionBatch)
                                <p class="text-xs font-semibold text-slate-700">Batch {{ $roll->productionBatch->nomor_merge }}</p>
                                <p class="mt-0.5 text-[11px] text-slate-500">{{ $roll->status === \App\Enums\RollStatus::ReadyToPack ? 'Siap packing' : 'Belum packing' }}</p>
                            @else
                                <p class="text-xs text-slate-400">Tanpa kardus</p>
                            @endif
                        </x-td>
                        <x-td>
                            <p class="text-xs font-medium text-slate-700">{{ $roll->specification }}</p>
                            <p class="mt-0.5 text-[11px] tabular-nums text-slate-500">{{ number_format($roll->denier) }}D · {{ number_format($roll->filamen) }}F</p>
                        </x-td>
                        <x-td align="right">
                            <p class="font-semibold tabular-nums text-slate-900">{{ number_format((float) $roll->berat_gram, 2, ',', '.') }} <span class="text-[11px] font-normal text-slate-500">g</span></p>
                            <p class="text-[11px] tabular-nums text-slate-500">{{ number_format((float) $roll->berat_gram / 1000, 2, ',', '.') }} kg</p>
                        </x-td>
                        <x-td align="right" class="pr-6">
                            <div class="flex items-center justify-end gap-1">
                                @if ($roll->status !== \App\Enums\RollStatus::Cancelled && $roll->status !== \App\Enums\RollStatus::Waste)
                                    <x-button href="{{ route('warehouse.rolls.label', $roll) }}" variant="ghost" size="sm" icon="printer" title="Cetak label">Label</x-button>
                                @endif
                                @if (in_array($roll->status, [\App\Enums\RollStatus::ReadyToPack, \App\Enums\RollStatus::Overweight, \App\Enums\RollStatus::Waste], true))
                                    <x-button href="{{ route('warehouse.rolls.edit', $roll) }}" variant="ghost" size="sm" icon="edit" title="Edit berat / nomor seri">Edit</x-button>
                                    <x-confirm-button
                                        :action="route('warehouse.rolls.cancel', $roll)"
                                        method="PATCH"
                                        title="Batalkan bobbin?"
                                        :description="'Bobbin '.$roll->nomor_seri.' akan ditandai batal. Riwayat tetap disimpan.'"
                                        note="Bobbin tidak dapat dipacking atau dikirim setelah dibatalkan."
                                        confirm-label="Ya, batalkan"
                                        variant="ghost"
                                        icon="ban"
                                    >
                                        Batalkan
                                    </x-confirm-button>
                                @elseif ($roll->status === \App\Enums\RollStatus::InStock && $roll->dty_carton_id === null)
                                    <x-confirm-button
                                        :action="route('warehouse.rolls.cancel', $roll)"
                                        method="PATCH"
                                        title="Batalkan bobbin?"
                                        :description="'Bobbin '.$roll->nomor_seri.' akan ditandai batal. Riwayat tetap disimpan.'"
                                        note="Bobbin tidak dapat dipacking atau dikirim setelah dibatalkan."
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
                    <tr>
                        <x-td colspan="6" class="py-12 text-center">
                            <x-icon name="package" class="mx-auto size-7 text-slate-300" />
                            <p class="mt-2 text-sm font-medium text-slate-600">Belum ada bobbin yang cocok</p>
                            <p class="mt-1 text-xs text-slate-500">Coba ubah filter status atau kata kunci pencarian.</p>
                        </x-td>
                    </tr>
                @endforelse
            </tbody>
        </x-table>

        <div class="space-y-3 md:hidden">
            @forelse ($rolls as $roll)
                @php
                    $grade = $roll->bobbin_weight_grade;
                    $gradeTone = match ($grade) {
                        'AX' => 'success',
                        'AE' => 'info',
                        'A' => 'warning',
                        'B' => 'neutral',
                        'OW' => 'warning',
                        'WASTE' => 'danger',
                        default => 'neutral',
                    };
                    $isAnomaly = in_array($roll->status, [\App\Enums\RollStatus::Overweight, \App\Enums\RollStatus::Waste], true);
                    $rowTone = $roll->status === \App\Enums\RollStatus::Waste
                        ? 'bg-rose-50/40'
                        : ($roll->status === \App\Enums\RollStatus::Overweight ? 'bg-amber-50/40' : '');
                @endphp

                <x-card padding="p-4" class="{{ $isAnomaly ? ($roll->status === \App\Enums\RollStatus::Waste ? 'ring-1 ring-inset ring-rose-200' : 'ring-1 ring-inset ring-amber-200') : '' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-start gap-2">
                            @if ($isAnomaly)
                                <x-icon name="alert" class="mt-0.5 size-3.5 shrink-0 text-amber-600" />
                            @endif
                            <div class="min-w-0">
                                <p class="font-mono text-[13px] font-semibold tracking-wide text-slate-900">{{ $roll->nomor_seri ?? '—' }}</p>
                                <p class="mt-0.5 text-[11px] text-slate-500">Lot <span class="font-medium text-slate-600">{{ $roll->nomor_lot }}</span></p>
                            </div>
                        </div>
                        <div class="shrink-0 text-right">
                            <x-status-badge :status="$roll->status" />
                            <div class="mt-1"><x-badge :tone="$gradeTone">Grade {{ $grade ?? '—' }}</x-badge></div>
                        </div>
                    </div>

                    <div class="mt-3 grid grid-cols-2 gap-x-3 gap-y-2 border-t border-slate-100/80 pt-3 text-xs">
                        <div>
                            <p class="text-[10px] uppercase tracking-wide text-slate-400">Spesifikasi</p>
                            <p class="mt-0.5 font-medium text-slate-700">{{ $roll->specification }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] uppercase tracking-wide text-slate-400">Berat</p>
                            <p class="mt-0.5 font-medium tabular-nums text-slate-700">{{ number_format((float) $roll->berat_gram, 2, ',', '.') }} g <span class="font-normal text-slate-400">/ {{ number_format((float) $roll->berat_gram / 1000, 2, ',', '.') }} kg</span></p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-[10px] uppercase tracking-wide text-slate-400">Kardus / Batch</p>
                            @if ($roll->carton)
                                <a href="{{ route('warehouse.cartons.label', $roll->carton) }}" class="mt-0.5 inline-flex items-center gap-1 font-mono font-semibold text-brand-700">
                                    <x-icon name="boxes" class="size-3.5" /> {{ $roll->carton->nomor_hu }}
                                </a>
                                <span class="ml-1 text-slate-500">· Merge {{ $roll->carton->nomor_merge }}</span>
                            @elseif ($roll->productionBatch)
                                <p class="mt-0.5 font-medium text-slate-700">Batch {{ $roll->productionBatch->nomor_merge }} <span class="font-normal text-slate-400">· {{ $roll->status === \App\Enums\RollStatus::ReadyToPack ? 'Siap packing' : 'Belum packing' }}</span></p>
                            @else
                                <p class="mt-0.5 text-slate-400">Tanpa kardus</p>
                            @endif
                        </div>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center justify-end gap-2 border-t border-slate-100/80 pt-3">
                        @if ($roll->status !== \App\Enums\RollStatus::Cancelled && $roll->status !== \App\Enums\RollStatus::Waste)
                            <x-button href="{{ route('warehouse.rolls.label', $roll) }}" variant="secondary" size="sm">Label</x-button>
                        @endif
                        @if (in_array($roll->status, [\App\Enums\RollStatus::ReadyToPack, \App\Enums\RollStatus::Overweight, \App\Enums\RollStatus::Waste], true))
                            <x-button href="{{ route('warehouse.rolls.edit', $roll) }}" variant="secondary" size="sm">Edit</x-button>
                            <x-confirm-button
                                :action="route('warehouse.rolls.cancel', $roll)"
                                method="PATCH"
                                title="Batalkan bobbin?"
                                :description="'Bobbin '.$roll->nomor_seri.' akan ditandai batal. Riwayat tetap disimpan.'"
                                note="Bobbin tidak dapat dipacking atau dikirim setelah dibatalkan."
                                confirm-label="Ya, batalkan"
                                variant="ghost"
                                icon="ban"
                            >
                                Batalkan
                            </x-confirm-button>
                        @elseif ($roll->status === \App\Enums\RollStatus::InStock && $roll->dty_carton_id === null)
                            <x-confirm-button
                                :action="route('warehouse.rolls.cancel', $roll)"
                                method="PATCH"
                                title="Batalkan bobbin?"
                                :description="'Bobbin '.$roll->nomor_seri.' akan ditandai batal. Riwayat tetap disimpan.'"
                                note="Bobbin tidak dapat dipacking atau dikirim setelah dibatalkan."
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
                <x-empty-state icon="package" title="Belum ada bobbin" description="Timbang bobbin dari batch produksi aktif untuk mulai mencetak label QR." />
            @endforelse
            <x-pagination :paginator="$rolls" />
        </div>
    </div>
@endsection
