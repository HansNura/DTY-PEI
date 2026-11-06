@extends('layouts.app')

@php
    $serial = $roll->nomor_seri ?? $roll->kode_rol;
    $carton = $roll->carton;
@endphp

@section('title', 'Label Bobbin '.$serial)

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <div class="print:hidden">
            <x-page-header title="Label identitas bobbin" description="Stiker individual per bobbin: QR serial, merge/lot, dan spesifikasi DTY. Cetak pada label thermal 50 × 30 mm skala 100%.">
                <x-slot:actions>
                    <x-button href="{{ route('warehouse.rolls.index') }}" variant="ghost" icon="arrow-left">Kembali</x-button>
                    <x-button type="button" data-print-label disabled x-data x-on:click="window.print()" icon="printer">Cetak label</x-button>
                </x-slot:actions>
            </x-page-header>

            <div class="mt-4">
                <p data-qr-error class="hidden text-sm text-rose-700" role="alert"></p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1.1fr_1fr] print:hidden">
            <x-card title="Pratinjau stiker" description="Tampilan diskalakan; ukuran cetak 50 × 30 mm.">
                <x-slot:actions>
                    <x-badge tone="neutral">50 × 30 mm</x-badge>
                </x-slot:actions>

                <div class="rounded-2xl bg-slate-100 p-4 sm:p-5">
                    <div class="mx-auto w-full max-w-[440px] overflow-hidden rounded-xl border-2 border-slate-400 bg-white shadow-xl sm:aspect-[5/3]">
                        <div class="flex h-full flex-col p-4 text-black">
                            <div class="flex items-start justify-between gap-3 border-b-2 border-slate-900 pb-2">
                                <div>
                                    <p class="text-base font-black leading-none tracking-tight">PMT</p>
                                    <p class="mt-1 text-[7px] uppercase tracking-[0.2em] text-slate-500">Drawn Textured Yarn</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-[7px] uppercase tracking-[0.16em] text-slate-500">Bobbin Identity</p>
                                    <p class="mt-1 text-[8px] font-semibold text-slate-700">Grade bobot {{ $roll->bobbin_weight_grade ?? '—' }}</p>
                                </div>
                            </div>

                            <div class="mt-3 flex min-h-0 flex-1 gap-4">
                                <div class="flex w-32 shrink-0 flex-col items-center justify-center">
                                    <canvas data-qr-code data-qr-token="{{ $qrToken }}" aria-label="Kode QR serial {{ $serial }}" class="size-32 shrink-0 rounded-sm bg-white"></canvas>
                                    <p class="mt-1.5 w-full break-all text-center font-mono text-[7px] font-semibold leading-tight text-black">{{ $serial }}</p>
                                </div>

                                <div class="flex min-w-0 flex-1 flex-col justify-between">
                                    <div>
                                        <p class="text-[8px] uppercase tracking-wide text-slate-500">Spesifikasi</p>
                                        <p class="text-lg font-black leading-tight sm:text-xl">{{ $roll->specification }}</p>
                                    </div>

                                    <div>
                                        <p class="text-[8px] uppercase tracking-wide text-slate-500">Merge / Lot</p>
                                        <p class="font-mono text-2xl font-black leading-none">{{ $roll->nomor_lot }}</p>
                                    </div>

                                    <div class="grid grid-cols-2 gap-x-3 gap-y-1 border-t border-slate-300 pt-2 text-[8px]">
                                        <div>
                                            <p class="uppercase text-slate-500">HU</p>
                                            <p class="truncate font-mono font-semibold">{{ $carton?->nomor_hu ?? '—' }}</p>
                                        </div>
                                        <div>
                                            <p class="uppercase text-slate-500">Pallet</p>
                                            <p class="truncate font-mono font-semibold">{{ $carton?->pallet_number ?? '—' }}</p>
                                        </div>
                                        <div>
                                            <p class="uppercase text-slate-500">Berat</p>
                                            <p class="font-semibold tabular-nums">{{ number_format((float) $roll->berat_gram, 0, ',', '.') }} g</p>
                                        </div>
                                        <div>
                                            <p class="uppercase text-slate-500">Grade</p>
                                            <p class="font-semibold">{{ $roll->bobbin_weight_grade ?? '—' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <p class="mt-3 text-xs leading-relaxed text-slate-500">QR berisi nomor seri bobbin. Pratinjau ini bukan ukuran cetak sebenarnya; gunakan tombol cetak untuk hasil 50 × 30 mm.</p>
            </x-card>

            <div class="space-y-6">
                <x-card title="Detail bobbin">
                    <x-slot:actions>
                        <x-status-badge :status="$roll->status" />
                    </x-slot:actions>

                    <dl class="grid gap-3 sm:grid-cols-2">
                        <div class="sm:col-span-2 rounded-xl bg-slate-50 px-3.5 py-3 ring-1 ring-inset ring-slate-100">
                            <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Nomor seri · payload QR</dt>
                            <dd class="mt-1 font-mono text-sm font-semibold tracking-wide text-slate-800">{{ $serial }}</dd>
                        </div>
                        <div class="sm:col-span-2 rounded-xl bg-slate-50 px-3.5 py-3 ring-1 ring-inset ring-slate-100">
                            <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Kode rol internal</dt>
                            <dd class="mt-1 font-mono text-xs font-medium text-slate-700">{{ $roll->kode_rol }}</dd>
                        </div>
                        <div class="rounded-xl bg-slate-50 px-3.5 py-3 ring-1 ring-inset ring-slate-100">
                            <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Merge / lot</dt>
                            <dd class="mt-1 font-mono text-sm font-semibold text-slate-800">{{ $roll->nomor_lot }}</dd>
                        </div>
                        <div class="rounded-xl bg-slate-50 px-3.5 py-3 ring-1 ring-inset ring-slate-100">
                            <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Spesifikasi</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-800">{{ $roll->specification }}</dd>
                        </div>
                        <div class="rounded-xl bg-slate-50 px-3.5 py-3 ring-1 ring-inset ring-slate-100">
                            <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Grade bobot</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-800">{{ $roll->bobbin_weight_grade ?? 'Di luar standar' }}</dd>
                        </div>
                        <div class="rounded-xl bg-slate-50 px-3.5 py-3 ring-1 ring-inset ring-slate-100">
                            <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Berat</dt>
                            <dd class="mt-1 text-sm font-semibold tabular-nums text-slate-800">{{ number_format((float) $roll->berat_gram, 2, ',', '.') }} g</dd>
                        </div>
                        <div class="rounded-xl bg-slate-50 px-3.5 py-3 ring-1 ring-inset ring-slate-100">
                            <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Nomor HU</dt>
                            <dd class="mt-1 font-mono text-sm font-semibold text-slate-800">{{ $carton?->nomor_hu ?? 'Input manual' }}</dd>
                        </div>
                        <div class="rounded-xl bg-slate-50 px-3.5 py-3 ring-1 ring-inset ring-slate-100">
                            <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Pallet</dt>
                            <dd class="mt-1 font-mono text-sm font-semibold text-slate-800">{{ $carton?->pallet_number ?? '—' }}</dd>
                        </div>
                    </dl>

                    <div class="mt-4 flex flex-wrap gap-2">
                        @if ($carton)
                            <x-button href="{{ route('warehouse.cartons.label', $carton) }}" variant="secondary" size="sm" icon="boxes">Label kardus</x-button>
                        @endif
                        @if ($roll->status === \App\Enums\RollStatus::InStock)
                            <x-button href="{{ route('warehouse.rolls.edit', $roll) }}" variant="secondary" size="sm" icon="edit">Edit bobbin</x-button>
                        @endif
                    </div>
                </x-card>

                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3.5 text-xs leading-relaxed text-amber-900">
                    <p class="font-semibold">Cara pakai stiker</p>
                    <p class="mt-1">Tempel pada paper tube atau plastik pembungkus bobbin. Scan QR saat sortir, inspeksi, packing, dan validasi muatan.</p>
                </div>
            </div>
        </div>

        {{-- Markup cetak: tetap, hanya terlihat saat print --}}
        <div class="thermal-label hidden items-center gap-1 overflow-hidden bg-white print:flex">
            <div class="flex w-[24mm] shrink-0 flex-col items-center">
                <canvas data-qr-code data-qr-token="{{ $qrToken }}" aria-label="Kode QR serial {{ $serial }}" class="size-20 shrink-0 rounded-sm bg-white"></canvas>
                <p class="mt-0.5 w-full break-all text-center font-mono text-[6px] font-semibold leading-tight text-black">{{ $serial }}</p>
            </div>
            <div class="min-w-0 flex-1 text-[7px] leading-[1.15] text-black">
                <p class="text-[9px] font-bold leading-tight">{{ $roll->specification }}</p>
                <p class="mt-1 text-[6px] uppercase tracking-wide">Merge / Lot</p>
                <p class="font-mono text-[10px] font-bold leading-tight">{{ $roll->nomor_lot }}</p>
                @if ($carton)
                    <p class="mt-1">HU {{ $carton->nomor_hu }}</p>
                    @if ($carton->pallet_number)
                        <p>Pallet {{ $carton->pallet_number }}</p>
                    @endif
                @endif
                <p class="mt-1">{{ number_format((float) $roll->berat_gram, 0, ',', '.') }} g · Grade {{ $roll->bobbin_weight_grade ?? '—' }}</p>
            </div>
        </div>
    </div>

    <style>
        @page { size: 50mm 30mm; margin: 0; }
        @media print {
            body * { visibility: hidden; }
            .thermal-label, .thermal-label * { visibility: visible; }
            .thermal-label {
                position: fixed;
                left: 0;
                top: 0;
                display: flex !important;
                width: 50mm;
                height: 30mm;
                padding: 1mm;
            }
            .thermal-label canvas { width: 20mm; height: 20mm; }
        }
    </style>
@endsection
