@extends('layouts.app')

@section('title', 'Label QR '.$roll->kode_rol)

@section('content')
    <div class="print:hidden">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Label QR gulungan</h1>
                <p class="mt-1 text-sm text-slate-500">Gunakan kertas label thermal 50 × 30 mm dan atur skala cetak ke 100%.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('warehouse.rolls.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">Kembali ke stok</a>
                <button type="button" data-print-label disabled x-data x-on:click="window.print()" class="rounded-lg bg-indigo-700 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-indigo-800 disabled:cursor-not-allowed disabled:opacity-50">
                    Cetak label
                </button>
            </div>
        </div>
        <p data-qr-error class="mt-4 hidden text-sm text-rose-700" role="alert"></p>
    </div>

    <div class="thermal-label mt-6 flex items-center gap-1 overflow-hidden rounded-lg border border-slate-300 bg-white p-1 shadow-sm print:mt-0 print:rounded-none print:border-0 print:p-0 print:shadow-none">
        <canvas data-qr-code data-qr-token="{{ $qrToken }}" aria-label="Kode QR untuk {{ $roll->kode_rol }}" class="size-20 shrink-0"></canvas>
        <div class="min-w-0 text-[8px] leading-tight text-black">
            <p class="break-all text-[10px] font-bold">{{ $roll->kode_rol }}</p>
            <p>Lot: {{ $roll->nomor_lot }}</p>
            <p>{{ $roll->denier }} D / {{ $roll->filamen }}F</p>
            <p>{{ number_format((float) $roll->berat_gram, 2, ',', '.') }} g</p>
        </div>
    </div>

    <style>
        @page { size: 50mm 30mm; margin: 0; }
        @media print {
            body { background: white !important; }
            body > header, body > footer, main > div > .print\:hidden { display: none !important; }
            main > div { max-width: none !important; padding: 0 !important; margin: 0 !important; }
            .thermal-label { width: 50mm; height: 30mm; }
            .thermal-label canvas { width: 22mm; height: 22mm; }
        }
    </style>
@endsection
