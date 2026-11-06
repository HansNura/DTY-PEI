@props(['carton'])

@php
    $carton->loadMissing('rolls');
    $drawerId = 'carton-drawer-'.uniqid();
    $weightGrade = $carton->bobbin_weight_grade;
    $range = \App\Support\BobbinWeightStandard::grossRangeFor($weightGrade ?? '', $carton->unit_bobbins);
    $grossWithin = $range !== null
        ? (float) $carton->gross_weight_kg >= $range['min'] && (float) $carton->gross_weight_kg <= $range['max']
        : null;
    $gradeTone = match ($weightGrade) {
        'AX' => 'success',
        'AE' => 'info',
        'A' => 'warning',
        'B' => 'danger',
        default => 'neutral',
    };
    $completion = $carton->unit_bobbins > 0
        ? min(100, (int) round($carton->rolls->count() / $carton->unit_bobbins * 100))
        : 0;
@endphp

<div x-data="{ open: false }" x-effect="document.body.classList.toggle('overflow-hidden', open)" class="inline-flex">
    <x-button type="button" variant="ghost" size="sm" icon="eye" x-on:click="open = true" data-carton-drawer>
        Info
    </x-button>

    <template x-teleport="body">
        <div x-cloak x-show="open" class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-labelledby="{{ $drawerId }}-title">
            <div class="absolute inset-0 bg-slate-950/40 backdrop-blur-[2px]" x-on:click="open = false"></div>

            <aside
                x-show="open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
                class="absolute inset-y-0 right-0 flex w-full max-w-lg flex-col border-l border-slate-200 bg-white shadow-2xl"
                x-on:keydown.escape.window="open = false"
            >
                <header class="border-b border-slate-100 px-5 py-5 sm:px-6">
                    <div class="flex items-start gap-3.5">
                        <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-brand-50 text-brand-700 ring-1 ring-brand-100">
                            <x-icon name="boxes" class="size-5" />
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-mono text-xs font-semibold text-slate-900">{{ $carton->nomor_hu }}</span>
                                <x-status-badge :status="$carton->status" />
                            </div>
                            <h2 id="{{ $drawerId }}-title" class="mt-1.5 truncate text-lg font-semibold tracking-tight text-slate-900">Merge {{ $carton->nomor_merge }}</h2>
                            <p class="mt-1 text-xs text-slate-500">{{ $carton->specification }} · Grade {{ $carton->grade }}</p>
                        </div>

                        <button type="button" x-on:click="open = false" class="-mr-1 -mt-1 rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" aria-label="Tutup info kardus">
                            <x-icon name="x" class="size-4" />
                        </button>
                    </div>
                </header>

                <div class="flex-1 divide-y divide-slate-100 overflow-y-auto px-5 sm:px-6">
                    <section class="py-5">
                        <h3 class="text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">Ringkasan kardus</h3>
                        <dl class="mt-3 grid grid-cols-2 gap-3">
                            <div class="rounded-xl bg-slate-50 px-3.5 py-3 ring-1 ring-inset ring-slate-100">
                                <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Produksi</dt>
                                <dd class="mt-1 text-sm text-slate-700">{{ $carton->production_date->translatedFormat('d M Y') }}</dd>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-3.5 py-3 ring-1 ring-inset ring-slate-100">
                                <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Pallet</dt>
                                <dd class="mt-1 font-mono text-sm text-slate-700">{{ $carton->pallet_number ?? '—' }}</dd>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-3.5 py-3 ring-1 ring-inset ring-slate-100">
                                <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Grade produk</dt>
                                <dd class="mt-1"><x-badge tone="brand">{{ $carton->grade }}</x-badge></dd>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-3.5 py-3 ring-1 ring-inset ring-slate-100">
                                <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Grade bobot</dt>
                                <dd class="mt-1"><x-badge :tone="$gradeTone">{{ $weightGrade ?? 'Di luar standar' }}</x-badge></dd>
                            </div>
                        </dl>
                    </section>

                    <section class="py-5">
                        <h3 class="text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">Berat</h3>
                        <dl class="mt-3 grid grid-cols-2 gap-3">
                            <div class="rounded-xl bg-slate-50 px-3.5 py-3 ring-1 ring-inset ring-slate-100">
                                <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Net</dt>
                                <dd class="mt-1 text-sm font-semibold tabular-nums text-slate-800">{{ number_format((float) $carton->net_weight_kg, 2, ',', '.') }} kg</dd>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-3.5 py-3 ring-1 ring-inset ring-slate-100">
                                <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Gross</dt>
                                <dd class="mt-1 text-sm font-semibold tabular-nums text-slate-800">{{ number_format((float) $carton->gross_weight_kg, 2, ',', '.') }} kg</dd>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-3.5 py-3 ring-1 ring-inset ring-slate-100">
                                <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Per bobbin</dt>
                                <dd class="mt-1 text-sm font-semibold tabular-nums text-slate-800">{{ number_format($carton->net_weight_per_bobbin_gram, 2, ',', '.') }} g</dd>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-3.5 py-3 ring-1 ring-inset ring-slate-100">
                                <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Isi bobbin</dt>
                                <dd class="mt-1 text-sm font-semibold tabular-nums text-slate-800">{{ $carton->rolls->count() }} / {{ $carton->unit_bobbins }}</dd>
                            </div>
                        </dl>

                        @if ($range !== null)
                            <div class="mt-3 rounded-xl px-3.5 py-3 text-xs ring-1 ring-inset {{ $grossWithin ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-rose-50 text-rose-700 ring-rose-100' }}">
                                Toleransi gross Grade AX · 6 bobbin: 34,74–36,25 kg (target 35,64 kg).
                                {{ $grossWithin ? 'Gross saat ini sesuai.' : 'Gross saat ini di luar toleransi.' }}
                            </div>
                        @endif
                    </section>

                    <section class="py-5">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">Bobbin dalam kardus</h3>
                            <span class="text-[11px] font-medium tabular-nums text-slate-500">{{ $completion }}%</span>
                        </div>
                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full {{ $completion === 100 ? 'bg-brand-500' : 'bg-amber-400' }}" style="width: {{ $completion }}%"></div>
                        </div>

                        <ul class="mt-3 max-h-64 divide-y divide-slate-100 overflow-y-auto">
                            @forelse ($carton->rolls as $roll)
                                <li class="flex items-center justify-between gap-3 py-2.5">
                                    <div class="min-w-0">
                                        <p class="font-mono text-xs font-semibold text-slate-800">{{ $roll->nomor_seri ?? $roll->kode_rol }}</p>
                                        <p class="font-mono text-[10px] text-slate-400">{{ $roll->kode_rol }}</p>
                                        <p class="text-[10px] text-slate-500">Lot {{ $roll->nomor_lot }} · {{ number_format((float) $roll->berat_gram, 0, ',', '.') }} g · Grade {{ $roll->bobbin_weight_grade ?? '—' }}</p>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-1.5">
                                        <x-status-badge :status="$roll->status" />
                                        @if ($roll->status !== \App\Enums\RollStatus::Cancelled)
                                            <a href="{{ route('warehouse.rolls.label', $roll) }}" class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-brand-700" aria-label="Cetak label {{ $roll->nomor_seri ?? $roll->kode_rol }}">
                                                <x-icon name="printer" class="size-3.5" />
                                            </a>
                                        @endif
                                    </div>
                                </li>
                            @empty
                                <li class="py-4 text-center text-sm text-slate-500">Belum ada bobbin.</li>
                            @endforelse
                        </ul>
                    </section>

                    @if ($carton->catatan)
                        <section class="py-5">
                            <h3 class="text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">Catatan</h3>
                            <p class="mt-2 rounded-xl bg-slate-50 px-3.5 py-3 text-sm leading-6 text-slate-600 ring-1 ring-inset ring-slate-100">{{ $carton->catatan }}</p>
                        </section>
                    @endif
                </div>

                <footer class="flex gap-2 border-t border-slate-100 px-5 py-4 sm:px-6">
                    <x-button href="{{ route('warehouse.cartons.label', $carton) }}" variant="secondary" size="sm" icon="printer" class="flex-1 justify-center">Label kardus</x-button>
                    @if ($carton->status === \App\Enums\RollStatus::InStock)
                        <x-button href="{{ route('warehouse.cartons.edit', $carton) }}" size="sm" icon="edit" class="flex-1 justify-center">Edit kardus</x-button>
                    @endif
                </footer>
            </aside>
        </div>
    </template>
</div>
