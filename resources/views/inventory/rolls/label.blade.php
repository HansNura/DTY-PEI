@extends('layouts.app')

@section('title', 'Label Bobbin '.($roll->nomor_seri ?? $roll->kode_rol))

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
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

        <div class="grid gap-6 lg:grid-cols-[1fr_1.2fr] print:hidden">
            <x-card title="Pratinjau label">
                <div class="thermal-label-preview rounded-xl border border-dashed border-slate-300 bg-white p-2.5">
                    <div class="flex items-start gap-2.5">
                        <div class="flex w-24 shrink-0 flex-col items-center">
                            <canvas data-qr-code data-qr-token="{{ $qrToken }}" aria-label="Kode QR serial {{ $roll->nomor_seri ?? $roll->kode_rol }}" class="size-20 shrink-0"></canvas>
                            <p class="mt-1 w-full break-all text-center font-mono text-[7px] font-semibold leading-tight">{{ $roll->nomor_seri ?? $roll->kode_rol }}</p>
                        </div>

                        <div class="min-w-0 flex-1 text-[9px] leading-tight text-black">
                            <p class="text-[11px] font-bold leading-tight">{{ $roll->specification }}</p>
                            <p class="mt-1.5 text-[7px] uppercase tracking-wide text-slate-500">Merge / Lot</p>
                            <p class="font-mono text-[10px] font-bold">{{ $roll->nomor_lot }}</p>
                            @if ($roll->carton)
                                <p class="mt-1 text-[8px]">HU {{ $roll->carton->nomor_hu }}</p>
                                @if ($roll->carton->pallet_number)
                                    <p class="text-[8px]">Pallet {{ $roll->carton->pallet_number }}</p>
                                @endif
                            @endif
                            <p class="mt-1 text-[8px]">{{ number_format((float) $roll->berat_gram, 0, ',', '.') }} g</p>
                        </div>
                    </div>
                </div>
                <p class="mt-3 text-xs text-slate-500">QR berisi nomor seri bobbin. Pastikan label terlihat sebelum mencetak.</p>
            </x-card>

            <x-card title="Detail bobbin">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Nomor seri</dt><dd class="font-mono font-medium text-slate-900">{{ $roll->nomor_seri ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Kode rol</dt><dd class="font-mono font-medium text-slate-900">{{ $roll->kode_rol }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Merge / lot</dt><dd class="font-medium text-slate-900">{{ $roll->nomor_lot }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Spesifikasi</dt><dd class="font-medium text-slate-900">{{ $roll->specification }}</dd></div>
                    @if ($roll->carton)
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Nomor HU</dt><dd class="font-mono font-medium text-slate-900">{{ $roll->carton->nomor_hu }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Pallet</dt><dd class="font-mono font-medium text-slate-900">{{ $roll->carton->pallet_number ?? '—' }}</dd></div>
                    @endif
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Berat</dt><dd class="font-medium tabular-nums text-slate-900">{{ number_format((float) $roll->berat_gram, 2, ',', '.') }} gram</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Status</dt><dd><x-status-badge :status="$roll->status" /></dd></div>
                </dl>
                <div class="mt-5 rounded-xl bg-amber-50 px-4 py-3 text-xs text-amber-800">
                    Cetak pada kertas thermal 50 × 30 mm. Jangan ubah skala cetak agar QR tetap terbaca.
                </div>
            </x-card>
        </div>

        {{-- Markup cetak: tetap, hanya terlihat saat print --}}
        <div class="thermal-label hidden items-center gap-1 overflow-hidden bg-white print:flex">
            <div class="flex w-[22mm] shrink-0 flex-col items-center">
                <canvas data-qr-code data-qr-token="{{ $qrToken }}" aria-label="Kode QR serial {{ $roll->nomor_seri ?? $roll->kode_rol }}" class="size-20 shrink-0"></canvas>
                <p class="mt-0.5 w-full break-all text-center font-mono text-[6px] font-semibold leading-tight text-black">{{ $roll->nomor_seri ?? $roll->kode_rol }}</p>
            </div>
            <div class="min-w-0 flex-1 text-[7px] leading-[1.15] text-black">
                <p class="text-[9px] font-bold leading-tight">{{ $roll->specification }}</p>
                <p class="mt-1 text-[6px] uppercase tracking-wide">Merge / Lot</p>
                <p class="font-mono text-[10px] font-bold leading-tight">{{ $roll->nomor_lot }}</p>
                @if ($roll->carton)
                    <p class="mt-1">HU {{ $roll->carton->nomor_hu }}</p>
                    @if ($roll->carton->pallet_number)
                        <p>Pallet {{ $roll->carton->pallet_number }}</p>
                    @endif
                @endif
                <p class="mt-1">{{ number_format((float) $roll->berat_gram, 0, ',', '.') }} g</p>
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
            .thermal-label canvas { width: 18mm; height: 18mm; }
        }
    </style>
@endsection
