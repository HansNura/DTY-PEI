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

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <x-stat-card label="Total kardus" :value="number_format($summary['cartons'], 0, ',', '.')" icon="boxes" tone="brand" hint="Seluruh kardus tercatat" />
            <x-stat-card label="Total bobbin" :value="number_format($summary['bobbins'], 0, ',', '.')" icon="package" tone="info" hint="Target isi seluruh kardus" />
            <x-stat-card label="Net weight" :value="number_format($summary['net_kg'], 2, ',', '.').' kg'" icon="download" tone="success" hint="Total berat benang" />
            <x-stat-card label="Gross weight" :value="number_format($summary['gross_kg'], 2, ',', '.').' kg'" icon="activity" tone="warning" hint="Total berat kardus + isi" />
        </div>

        <x-card padding="p-3 sm:p-4">
            <form method="GET" action="{{ route('warehouse.cartons.index') }}" class="space-y-3">
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1fr_auto] lg:items-end">
                    <x-field label="Cari" name="q">
                        <div class="relative">
                            <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                            <x-input id="q" name="q" type="search" value="{{ $search }}" placeholder="HU, lot/merge, grade, atau pallet" aria-label="Cari kardus" class="pl-10" />
                        </div>
                    </x-field>

                    <x-field label="Grade produk" name="grade">
                        <x-select id="grade" name="grade" aria-label="Filter grade produk">
                            <option value="">Semua grade</option>
                            @foreach ($grades as $gradeOption)
                                <option value="{{ $gradeOption }}" @selected($grade === $gradeOption)>{{ $gradeOption }}</option>
                            @endforeach
                        </x-select>
                    </x-field>

                    <x-field label="Grade bobot" name="weight_grade">
                        <x-select id="weight_grade" name="weight_grade" aria-label="Filter grade bobot">
                            <option value="">Semua bobot</option>
                            <option value="AX" @selected($weightGrade === 'AX')>AX (5.350–5.600 g)</option>
                            <option value="AE" @selected($weightGrade === 'AE')>AE (5.100–5.349 g)</option>
                            <option value="A" @selected($weightGrade === 'A')>A (1.000–5.099 g)</option>
                            <option value="B" @selected($weightGrade === 'B')>B (500–999 g)</option>
                            <option value="out" @selected($weightGrade === 'out')>Di luar standar</option>
                        </x-select>
                    </x-field>

                    <x-field label="Bulan produksi" name="month">
                        <x-input id="month" name="month" type="month" value="{{ $month }}" aria-label="Filter bulan produksi" />
                    </x-field>

                    <x-button type="submit" icon="filter" class="w-full justify-center lg:w-auto">Terapkan</x-button>
                </div>
            </form>

            <div class="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-3">
                <p class="text-xs text-slate-500">
                    Menampilkan <span class="font-semibold text-slate-700">{{ $cartons->total() }}</span> kardus
                    @if ($search !== '')
                        untuk <span class="font-medium text-slate-700">“{{ $search }}”</span>
                    @endif
                    @if ($grade !== '')
                        dengan grade <span class="font-medium text-slate-700">{{ $grade }}</span>
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
                    @php
                        $completion = $carton->unit_bobbins > 0
                            ? min(100, (int) round($carton->rolls->count() / $carton->unit_bobbins * 100))
                            : 0;
                        $weightTone = match ($carton->bobbin_weight_grade) {
                            'AX' => 'success',
                            'AE' => 'info',
                            'A' => 'warning',
                            'B' => 'danger',
                            default => 'neutral',
                        };
                    @endphp
                    <tr class="hover:bg-slate-50/60">
                        <x-td>
                            <p class="font-mono text-xs font-semibold text-slate-900">{{ $carton->nomor_hu }}</p>
                            <p class="text-xs text-slate-500">Merge {{ $carton->nomor_merge }}</p>
                            @if ($carton->pallet_number)
                                <p class="text-[11px] text-slate-400">Pallet {{ $carton->pallet_number }}</p>
                            @endif
                        </x-td>
                        <x-td class="tabular-nums">{{ $carton->specification }}</x-td>
                        <x-td>
                            <x-badge tone="brand">{{ $carton->grade }}</x-badge>
                            <div class="mt-1.5">
                                <x-badge :tone="$weightTone">Bobot {{ $carton->bobbin_weight_grade ?? '—' }}</x-badge>
                            </div>
                        </x-td>
                        <x-td>{{ $carton->production_date->translatedFormat('d M Y') }}</x-td>
                        <x-td class="tabular-nums">{{ number_format((float) $carton->net_weight_kg, 2, ',', '.') }} / {{ number_format((float) $carton->gross_weight_kg, 2, ',', '.') }} kg</x-td>
                        <x-td>
                            <div class="flex items-center gap-2">
                                <div class="h-1.5 w-16 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full {{ $completion === 100 ? 'bg-brand-500' : 'bg-amber-400' }}" style="width: {{ $completion }}%"></div>
                                </div>
                                <span class="text-xs font-medium tabular-nums text-slate-600">{{ $carton->rolls->count() }}/{{ $carton->unit_bobbins }}</span>
                            </div>
                        </x-td>
                        <x-td><x-status-badge :status="$carton->status" /></x-td>
                        <x-td align="right">
                            <div class="flex flex-wrap items-center justify-end gap-2">
                                <x-carton-drawer :carton="$carton" />
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
                @php
                    $completion = $carton->unit_bobbins > 0
                        ? min(100, (int) round($carton->rolls->count() / $carton->unit_bobbins * 100))
                        : 0;
                    $weightTone = match ($carton->bobbin_weight_grade) {
                        'AX' => 'success',
                        'AE' => 'info',
                        'A' => 'warning',
                        'B' => 'danger',
                        default => 'neutral',
                    };
                @endphp
                <x-card padding="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-mono text-xs font-semibold text-slate-900">{{ $carton->nomor_hu }}</p>
                            <p class="text-xs text-slate-500">Merge {{ $carton->nomor_merge }} · {{ $carton->grade }}</p>
                            @if ($carton->pallet_number)
                                <p class="text-[11px] text-slate-400">Pallet {{ $carton->pallet_number }}</p>
                            @endif
                        </div>
                        <x-status-badge :status="$carton->status" />
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <x-badge tone="brand">{{ $carton->grade }}</x-badge>
                        <x-badge :tone="$weightTone">Bobot {{ $carton->bobbin_weight_grade ?? '—' }}</x-badge>
                    </div>

                    <dl class="mt-3 grid grid-cols-2 gap-2 text-xs">
                        <div><dt class="text-slate-500">Spesifikasi</dt><dd class="font-medium text-slate-800">{{ $carton->specification }}</dd></div>
                        <div><dt class="text-slate-500">Produksi</dt><dd class="font-medium text-slate-800">{{ $carton->production_date->translatedFormat('d M Y') }}</dd></div>
                        <div><dt class="text-slate-500">Net / gross</dt><dd class="font-medium tabular-nums text-slate-800">{{ number_format((float) $carton->net_weight_kg, 2, ',', '.') }} / {{ number_format((float) $carton->gross_weight_kg, 2, ',', '.') }} kg</dd></div>
                        <div>
                            <dt class="text-slate-500">Bobbin</dt>
                            <dd class="mt-1 flex items-center gap-2 font-medium tabular-nums text-slate-800">
                                <span>{{ $carton->rolls->count() }}/{{ $carton->unit_bobbins }}</span>
                                <span class="h-1.5 w-12 overflow-hidden rounded-full bg-slate-100">
                                    <span class="block h-full rounded-full {{ $completion === 100 ? 'bg-brand-500' : 'bg-amber-400' }}" style="width: {{ $completion }}%"></span>
                                </span>
                            </dd>
                        </div>
                    </dl>

                    <div class="mt-4 flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 pt-3">
                        <x-carton-drawer :carton="$carton" />
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
