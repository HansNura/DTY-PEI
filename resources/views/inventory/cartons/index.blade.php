@extends('layouts.app')

@section('title', 'Kardus DTY')

@section('content')
    <div class="space-y-6">
        <x-page-header title="Kardus DTY" description="Daftar kardus hasil packing: nomor HU, merge, isi bobbin, berat, dan status kirim.">
            <x-slot:actions>
                <x-button href="{{ route('warehouse.rolls.index') }}" variant="secondary" icon="package">Stok bobbin</x-button>
                <x-button href="{{ route('warehouse.packing.create') }}" icon="boxes">Packing kardus</x-button>
            </x-slot:actions>
        </x-page-header>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <x-stat-card label="Total kardus" :value="number_format($summary['cartons'], 0, ',', '.')" icon="boxes" tone="brand" hint="Seluruh kardus tercatat" />
            <x-stat-card label="Total bobbin" :value="number_format($summary['bobbins'], 0, ',', '.')" icon="package" tone="info" hint="Target isi seluruh kardus" />
            <x-stat-card label="Net weight" :value="number_format($summary['net_kg'], 2, ',', '.').' kg'" icon="download" tone="success" hint="Total berat benang" />
            <x-stat-card label="Gross weight" :value="number_format($summary['gross_kg'], 2, ',', '.').' kg'" icon="activity" tone="warning" hint="Total berat kardus + isi" />
        </div>

        <x-card padding="p-3 sm:p-4">
            <form method="GET" action="{{ route('warehouse.cartons.index') }}" class="space-y-3">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative min-w-0 flex-1">
                        <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                        <x-input id="q" name="q" type="search" value="{{ $search }}" placeholder="Cari HU, merge, grade, atau pallet" aria-label="Cari kardus" class="pl-10" />
                    </div>

                    <div class="flex items-center gap-2">
                        <div class="relative">
                            <x-icon name="calendar" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                            <x-input id="month" name="month" type="month" value="{{ $month }}" aria-label="Filter bulan produksi" class="w-full pl-9 sm:w-44" />
                        </div>
                        <x-button type="submit" icon="filter" class="justify-center">Terapkan</x-button>
                    </div>
                </div>

                @php
                    $productGradeFilters = [['value' => '', 'label' => 'Semua', 'count' => $summary['cartons']]];

                    foreach ($grades as $gradeOption) {
                        $productGradeFilters[] = [
                            'value' => $gradeOption,
                            'label' => $gradeOption,
                            'count' => (int) ($gradeCounts[$gradeOption] ?? 0),
                        ];
                    }

                    $weightGradeFilters = [
                        ['value' => '', 'label' => 'Semua', 'count' => $summary['cartons'], 'dot' => 'bg-slate-400'],
                        ['value' => 'AX', 'label' => 'AX', 'count' => (int) ($weightGradeCounts['AX'] ?? 0), 'dot' => 'bg-emerald-500'],
                        ['value' => 'AE', 'label' => 'AE', 'count' => (int) ($weightGradeCounts['AE'] ?? 0), 'dot' => 'bg-sky-500'],
                        ['value' => 'A', 'label' => 'A', 'count' => (int) ($weightGradeCounts['A'] ?? 0), 'dot' => 'bg-amber-500'],
                        ['value' => 'B', 'label' => 'B', 'count' => (int) ($weightGradeCounts['B'] ?? 0), 'dot' => 'bg-rose-500'],
                        ['value' => 'out', 'label' => 'Di luar standar', 'count' => (int) ($weightGradeCounts['out'] ?? 0), 'dot' => 'bg-slate-500'],
                    ];
                @endphp

                <div class="grid gap-3 border-t border-slate-100 pt-3 lg:grid-cols-2">
                    <fieldset>
                        <legend class="text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">Grade produk</legend>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            @foreach ($productGradeFilters as $filter)
                                <label class="cursor-pointer select-none">
                                    <input type="radio" name="grade" value="{{ $filter['value'] }}" class="peer sr-only" @checked($grade === $filter['value']) onchange="this.form.requestSubmit()" />
                                    <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 peer-checked:border-brand-200 peer-checked:bg-brand-50 peer-checked:text-brand-700 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500/40">
                                        <span class="size-1.5 rounded-full bg-brand-500"></span>
                                        {{ $filter['label'] }}
                                        <span class="rounded-full bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold tabular-nums text-slate-500">{{ number_format($filter['count'], 0, ',', '.') }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend class="text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">Grade bobot</legend>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            @foreach ($weightGradeFilters as $filter)
                                <label class="cursor-pointer select-none">
                                    <input type="radio" name="weight_grade" value="{{ $filter['value'] }}" class="peer sr-only" @checked($weightGrade === $filter['value']) onchange="this.form.requestSubmit()" />
                                    <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 peer-checked:border-brand-200 peer-checked:bg-brand-50 peer-checked:text-brand-700 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500/40">
                                        <span class="size-1.5 rounded-full {{ $filter['dot'] }}"></span>
                                        {{ $filter['label'] }}
                                        <span class="rounded-full bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold tabular-nums text-slate-500">{{ number_format($filter['count'], 0, ',', '.') }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-3">
                    <p class="text-xs text-slate-500">
                        Menampilkan <span class="font-semibold text-slate-700">{{ $cartons->total() }}</span> kardus
                        @if ($search !== '')
                            untuk <span class="font-medium text-slate-700">“{{ $search }}”</span>
                        @endif
                        @if ($grade !== '')
                            · grade <span class="font-medium text-slate-700">{{ $grade }}</span>
                        @endif
                        @if ($weightGrade !== '')
                            · bobot <span class="font-medium text-slate-700">{{ $weightGrade === 'out' ? 'di luar standar' : $weightGrade }}</span>
                        @endif
                        @if ($month !== '')
                            · produksi <span class="font-medium text-slate-700">{{ $month }}</span>
                        @endif
                    </p>

                    @if ($search !== '' || $grade !== '' || $weightGrade !== '' || $month !== '')
                        <a href="{{ route('warehouse.cartons.index') }}" class="inline-flex items-center gap-1 text-xs font-medium text-brand-700 transition hover:text-brand-900">
                            <x-icon name="x" class="size-3.5" /> Reset filter
                        </a>
                    @endif
                </div>
            </form>
        </x-card>

        <x-table class="hidden md:block" padding="p-0">
            <x-slot:footer><x-pagination :paginator="$cartons" class="!mt-0" /></x-slot:footer>

            <thead class="bg-slate-50/80">
                <tr>
                    <x-th class="pl-6">Kardus</x-th>
                    <x-th>Status &amp; Grade</x-th>
                    <x-th>Spesifikasi</x-th>
                    <x-th>Produksi</x-th>
                    <x-th align="right">Berat</x-th>
                    <x-th>Isi bobbin</x-th>
                    <x-th align="right" class="pr-6">Aksi</x-th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($cartons as $carton)
                    @php
                        $completion = $carton->unit_bobbins > 0
                            ? min(100, (int) round($carton->rolls->count() / $carton->unit_bobbins * 100))
                            : 0;
                        $weightTone = match ($carton->bobbin_weight_grade) {
                            'AX' => 'success',
                            'AE' => 'info',
                            'A' => 'warning',
                            'B' => 'neutral',
                            'OW' => 'warning',
                            'WASTE' => 'danger',
                            default => 'neutral',
                        };
                    @endphp

                    <tr class="transition hover:bg-slate-50/70">
                        <x-td class="pl-6">
                            <div class="flex items-start gap-2.5">
                                <span class="mt-0.5 grid size-8 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-100">
                                    <x-icon name="boxes" class="size-4" />
                                </span>
                                <div class="min-w-0">
                                    <p class="font-mono text-[13px] font-semibold tracking-wide text-slate-900">{{ $carton->nomor_hu }}</p>
                                    <p class="mt-0.5 text-[11px] text-slate-500">Merge <span class="font-medium text-slate-600">{{ $carton->nomor_merge }}</span></p>
                                    @if ($carton->pallet_number)
                                        <p class="text-[11px] text-slate-400">Pallet {{ $carton->pallet_number }}</p>
                                    @endif
                                </div>
                            </div>
                        </x-td>
                        <x-td>
                            <div class="flex flex-wrap items-center gap-1.5">
                                <x-status-badge :status="$carton->status" />
                                <x-badge tone="brand">Produk {{ $carton->grade }}</x-badge>
                                <x-badge :tone="$weightTone">Bobot {{ $carton->bobbin_weight_grade ?? '—' }}</x-badge>
                            </div>
                        </x-td>
                        <x-td>
                            <p class="text-xs font-medium text-slate-700">{{ $carton->specification }}</p>
                            <p class="mt-0.5 text-[11px] tabular-nums text-slate-500">{{ number_format($carton->denier) }}D · {{ number_format($carton->filamen) }}F</p>
                        </x-td>
                        <x-td>
                            <p class="text-xs font-medium text-slate-700">{{ $carton->production_date->translatedFormat('d M Y') }}</p>
                            <p class="mt-0.5 text-[11px] text-slate-500">{{ $carton->production_date->format('Y') }}</p>
                        </x-td>
                        <x-td align="right">
                            <p class="text-xs text-slate-500"><span class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Net</span> <span class="ml-1 font-semibold tabular-nums text-slate-900">{{ number_format((float) $carton->net_weight_kg, 2, ',', '.') }} kg</span></p>
                            <p class="mt-0.5 text-xs text-slate-500"><span class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Gross</span> <span class="ml-1 font-semibold tabular-nums text-slate-900">{{ number_format((float) $carton->gross_weight_kg, 2, ',', '.') }} kg</span></p>
                        </x-td>
                        <x-td>
                            <div class="flex items-center gap-2">
                                <div class="h-1.5 w-20 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full {{ $completion === 100 ? 'bg-brand-500' : 'bg-amber-400' }}" style="width: {{ $completion }}%"></div>
                                </div>
                                <span class="text-xs font-medium tabular-nums text-slate-600">{{ $carton->rolls->count() }}/{{ $carton->unit_bobbins }}</span>
                            </div>
                            <p class="mt-0.5 text-[11px] text-slate-500">{{ $completion }}% lengkap</p>
                        </x-td>
                        <x-td align="right" class="pr-6">
                            <div class="flex flex-wrap items-center justify-end gap-1">
                                <x-carton-drawer :carton="$carton" />
                                <x-button href="{{ route('warehouse.cartons.label', $carton) }}" variant="ghost" size="sm" icon="printer" title="Cetak label kardus">Label</x-button>
                                @if ($carton->status === \App\Enums\RollStatus::InStock)
                                    <x-button href="{{ route('warehouse.cartons.edit', $carton) }}" variant="ghost" size="sm" icon="edit" title="Edit gross / pallet / catatan">Edit</x-button>
                                @endif
                            </div>
                        </x-td>
                    </tr>
                @empty
                    <tr>
                        <x-td colspan="7" class="py-12 text-center">
                            <x-icon name="boxes" class="mx-auto size-7 text-slate-300" />
                            <p class="mt-2 text-sm font-medium text-slate-600">Belum ada kardus yang cocok</p>
                            <p class="mt-1 text-xs text-slate-500">Coba ubah filter grade, bobot, bulan, atau kata kunci.</p>
                        </x-td>
                    </tr>
                @endforelse
            </tbody>
        </x-table>

        <div class="space-y-3 md:hidden">
            @forelse ($cartons as $carton)
                @php
                    $completion = $carton->unit_bobbins > 0
                        ? min(100, (int) round($carton->rolls->count() / $carton->unit_bobbins * 100))
                        : 0;
                    $weightTone = match ($carton->bobbin_weight_grade) {
                        'AX' => 'success',
                        'AE' => 'info',
                        'A' => 'warning',
                        'B' => 'neutral',
                        'OW' => 'warning',
                        'WASTE' => 'danger',
                        default => 'neutral',
                    };
                @endphp

                <x-card padding="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-start gap-2.5">
                            <span class="mt-0.5 grid size-8 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-100">
                                <x-icon name="boxes" class="size-4" />
                            </span>
                            <div class="min-w-0">
                                <p class="font-mono text-[13px] font-semibold tracking-wide text-slate-900">{{ $carton->nomor_hu }}</p>
                                <p class="mt-0.5 text-[11px] text-slate-500">Merge {{ $carton->nomor_merge }}</p>
                                @if ($carton->pallet_number)
                                    <p class="text-[11px] text-slate-400">Pallet {{ $carton->pallet_number }}</p>
                                @endif
                            </div>
                        </div>
                        <x-status-badge :status="$carton->status" />
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-1.5">
                        <x-badge tone="brand">Produk {{ $carton->grade }}</x-badge>
                        <x-badge :tone="$weightTone">Bobot {{ $carton->bobbin_weight_grade ?? '—' }}</x-badge>
                    </div>

                    <dl class="mt-3 grid grid-cols-2 gap-x-3 gap-y-2 border-t border-slate-100/80 pt-3 text-xs">
                        <div><dt class="text-[10px] uppercase tracking-wide text-slate-400">Spesifikasi</dt><dd class="mt-0.5 font-medium text-slate-700">{{ $carton->specification }}</dd></div>
                        <div><dt class="text-[10px] uppercase tracking-wide text-slate-400">Produksi</dt><dd class="mt-0.5 font-medium text-slate-700">{{ $carton->production_date->translatedFormat('d M Y') }}</dd></div>
                        <div><dt class="text-[10px] uppercase tracking-wide text-slate-400">Net</dt><dd class="mt-0.5 font-semibold tabular-nums text-slate-800">{{ number_format((float) $carton->net_weight_kg, 2, ',', '.') }} kg</dd></div>
                        <div><dt class="text-[10px] uppercase tracking-wide text-slate-400">Gross</dt><dd class="mt-0.5 font-semibold tabular-nums text-slate-800">{{ number_format((float) $carton->gross_weight_kg, 2, ',', '.') }} kg</dd></div>
                        <div class="col-span-2">
                            <dt class="text-[10px] uppercase tracking-wide text-slate-400">Isi bobbin</dt>
                            <dd class="mt-1 flex items-center gap-2 font-medium tabular-nums text-slate-800">
                                <span>{{ $carton->rolls->count() }}/{{ $carton->unit_bobbins }}</span>
                                <span class="h-1.5 w-16 overflow-hidden rounded-full bg-slate-100">
                                    <span class="block h-full rounded-full {{ $completion === 100 ? 'bg-brand-500' : 'bg-amber-400' }}" style="width: {{ $completion }}%"></span>
                                </span>
                                <span class="text-[11px] font-normal text-slate-500">{{ $completion }}%</span>
                            </dd>
                        </div>
                    </dl>

                    <div class="mt-3 flex flex-wrap items-center justify-end gap-2 border-t border-slate-100/80 pt-3">
                        <x-carton-drawer :carton="$carton" />
                        <x-button href="{{ route('warehouse.cartons.label', $carton) }}" variant="secondary" size="sm" icon="printer">Label</x-button>
                        @if ($carton->status === \App\Enums\RollStatus::InStock)
                            <x-button href="{{ route('warehouse.cartons.edit', $carton) }}" variant="secondary" size="sm" icon="edit">Edit</x-button>
                        @endif
                    </div>
                </x-card>
            @empty
                <x-empty-state icon="boxes" title="Belum ada kardus" description="Lakukan packing bobbin untuk membuat kardus pertama." />
            @endforelse
            <x-pagination :paginator="$cartons" />
        </div>
    </div>
@endsection
