@extends('layouts.app')

@section('title', 'Label QR '.$roll->kode_rol)

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <div class="print:hidden">
            <x-page-header title="Label QR gulungan" description="Gunakan kertas label thermal 50 × 30 mm dan atur skala cetak 100%.">
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
                <div class="thermal-label-preview flex items-center gap-2 rounded-xl border border-dashed border-slate-300 bg-white p-2">
                    <canvas data-qr-code data-qr-token="{{ $qrToken }}" aria-label="Kode QR untuk {{ $roll->kode_rol }}" class="size-24 shrink-0"></canvas>
                    <div class="min-w-0 text-[9px] leading-tight text-black">
                        <p class="break-all text-[11px] font-bold">{{ $roll->kode_rol }}</p>
                        <p>Lot: {{ $roll->nomor_lot }}</p>
                        <p>{{ $roll->denier }} D / {{ $roll->filamen }}F</p>
                        <p>{{ number_format((float) $roll->berat_gram, 2, ',', '.') }} g</p>
                    </div>
                </div>
                <p class="mt-3 text-xs text-slate-500">QR dibuat di browser. Pastikan label terlihat sebelum mencetak.</p>
            </x-card>

            <x-card title="Detail gulungan">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Kode rol</dt><dd class="font-mono font-medium text-slate-900">{{ $roll->kode_rol }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Nomor lot</dt><dd class="font-medium text-slate-900">{{ $roll->nomor_lot }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Spesifikasi</dt><dd class="font-medium text-slate-900">{{ $roll->denier }} D / {{ $roll->filamen }} F</dd></div>
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
            <canvas data-qr-code data-qr-token="{{ $qrToken }}" aria-label="Kode QR untuk {{ $roll->kode_rol }}" class="size-20 shrink-0"></canvas>
            <div class="min-w-0 text-[8px] leading-tight text-black">
                <p class="break-all text-[10px] font-bold">{{ $roll->kode_rol }}</p>
                <p>Lot: {{ $roll->nomor_lot }}</p>
                <p>{{ $roll->denier }} D / {{ $roll->filamen }}F</p>
                <p>{{ number_format((float) $roll->berat_gram, 2, ',', '.') }} g</p>
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
            .thermal-label canvas { width: 22mm; height: 22mm; }
        }
    </style>
@endsection
