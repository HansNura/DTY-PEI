@extends('layouts.app')

@section('title', 'Label Kardus ' . $carton->nomor_hu)

@section('content')
    <div class="mx-auto max-w-4xl space-y-6">
        <div class="print:hidden">
            <x-page-header title="Label kardus DTY" description="Cetak pada kertas label 100 × 150 mm dengan skala 100%.">
                <x-slot:actions>
                    <x-button href="{{ route('warehouse.cartons.index') }}" variant="ghost" icon="arrow-left">Kembali</x-button>
                    <x-button type="button" data-print-label disabled x-data x-on:click="window.print()" icon="printer">Cetak label</x-button>
                </x-slot:actions>
            </x-page-header>

            <p data-qr-error class="mt-4 hidden text-sm text-rose-700" role="alert"></p>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1fr_1fr] print:block">
            <x-card title="Pratinjau label" class="print:border-0 print:shadow-none">
                <div class="carton-label mx-auto w-full max-w-[100mm] border-2 border-slate-900 bg-white p-4 text-black" style="min-height: 150mm;">
                    <div class="flex items-start justify-between border-b-2 border-slate-900 pb-2">
                        <div>
                            <p class="text-lg font-bold leading-none">PMT</p>
                            <p class="mt-1 text-[9px] uppercase tracking-wider">Drawn Textured Yarn</p>
                        </div>
                        <p class="text-right text-[9px] uppercase tracking-wider">Product Label</p>
                    </div>

                    <div class="mt-3 border-2 border-slate-900 px-3 py-2 text-center">
                        <p class="text-[9px] uppercase tracking-wider">Merge</p>
                        <p class="mt-0.5 font-mono text-xl font-bold leading-none">{{ $carton->nomor_merge }} {{ $carton->grade }}</p>
                    </div>

                    <div class="mt-3 grid grid-cols-[1fr_28mm] gap-3">
                        <div>
                            <p class="text-base font-bold">DTY {{ $carton->specification }}</p>

                            <dl class="mt-3 space-y-1.5 text-[9px]">
                                <div class="flex justify-between gap-2 border-b border-slate-300 pb-1"><dt>GROSS WEIGHT (KG)</dt><dd class="font-bold tabular-nums">{{ number_format((float) $carton->gross_weight_kg, 2, '.', '') }}</dd></div>
                                <div class="flex justify-between gap-2 border-b border-slate-300 pb-1"><dt>NET WEIGHT (KG)</dt><dd class="font-bold tabular-nums">{{ number_format((float) $carton->net_weight_kg, 2, '.', '') }}</dd></div>
                                <div class="flex justify-between gap-2 border-b border-slate-300 pb-1"><dt>UNIT BOBBINS (QTY)</dt><dd class="font-bold tabular-nums">{{ $carton->unit_bobbins }}</dd></div>
                                <div class="flex justify-between gap-2 border-b border-slate-300 pb-1"><dt>GRADE</dt><dd class="font-bold">{{ $carton->grade }}</dd></div>
                                <div class="flex justify-between gap-2"><dt>PRODUCTION DATE</dt><dd class="font-bold tabular-nums">{{ $carton->production_date->format('d.m.Y') }}</dd></div>
                            </dl>
                        </div>

                        <div class="flex flex-col items-center justify-start">
                            <canvas data-qr-code data-qr-token="{{ $qrToken }}" aria-label="Kode QR kardus {{ $carton->nomor_hu }}" class="size-28"></canvas>
                            <p class="mt-1 text-center font-mono text-[8px] font-bold leading-tight">{{ $carton->nomor_hu }}</p>
                        </div>
                    </div>

                    <div class="mt-3 space-y-1 border-t-2 border-slate-900 pt-2 text-[9px]">
                        <div class="flex justify-between gap-2"><span>CARTON / HU NUMBER</span><span class="font-mono font-bold">{{ $carton->nomor_hu }}</span></div>
                        <div class="flex justify-between gap-2"><span>LOT NO / MERGE NO</span><span class="font-mono font-bold">{{ $carton->nomor_merge }}</span></div>
                        <div class="flex justify-between gap-2"><span>PALLET NO</span><span class="font-mono font-bold">{{ $carton->pallet_number ?? '—' }}</span></div>
                    </div>

                    <p class="mt-3 text-center text-[7px] uppercase tracking-wider">Simpan label ini sampai barang diterima pelanggan</p>
                </div>
            </x-card>

            <div class="space-y-6 print:hidden">
                <x-card title="Detail kardus">
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Nomor HU</dt><dd class="font-mono font-medium text-slate-900">{{ $carton->nomor_hu }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Lot / merge</dt><dd class="font-mono font-medium text-slate-900">{{ $carton->nomor_merge }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Spesifikasi</dt><dd class="font-medium text-slate-900">DTY {{ $carton->specification }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Grade</dt><dd class="font-medium text-slate-900">{{ $carton->grade }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Produksi</dt><dd class="font-medium text-slate-900">{{ $carton->production_date->translatedFormat('d M Y') }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Net / gross</dt><dd class="font-medium tabular-nums text-slate-900">{{ number_format((float) $carton->net_weight_kg, 2, ',', '.') }} / {{ number_format((float) $carton->gross_weight_kg, 2, ',', '.') }} kg</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Per bobbin</dt><dd class="font-medium tabular-nums text-slate-900">{{ number_format($carton->net_weight_per_bobbin_gram, 2, ',', '.') }} g</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Pallet</dt><dd class="font-mono font-medium text-slate-900">{{ $carton->pallet_number ?? '—' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Status</dt><dd><x-status-badge :status="$carton->status" /></dd></div>
                    </dl>
                </x-card>

                <x-card padding="p-0" title="Bobbin dalam kardus" :description="$carton->rolls->count().' dari '.$carton->unit_bobbins.' bobbin'">
                    <ul class="max-h-72 divide-y divide-slate-100 overflow-y-auto">
                        @forelse ($carton->rolls as $roll)
                            <li class="flex items-center justify-between gap-3 px-5 py-3">
                                <div class="min-w-0">
                                    <p class="font-mono text-xs font-semibold text-slate-800">{{ $roll->kode_rol }}</p>
                                    <p class="font-mono text-[10px] text-slate-500">{{ $roll->nomor_seri ?? '—' }}</p>
                                    <p class="text-xs text-slate-500">Lot {{ $roll->nomor_lot }} · {{ number_format((float) $roll->berat_gram, 2, ',', '.') }} g</p>
                                </div>
                                <x-status-badge :status="$roll->status" />
                            </li>
                        @empty
                            <li class="px-5 py-6 text-center text-sm text-slate-500">Belum ada bobbin.</li>
                        @endforelse
                    </ul>
                </x-card>
            </div>
        </div>
    </div>

    <style>
        @page { size: 100mm 150mm; margin: 0; }
        @media print {
            body * { visibility: hidden; }
            .carton-label, .carton-label * { visibility: visible; }
            .carton-label {
                position: fixed;
                left: 0;
                top: 0;
                width: 100mm;
                height: 150mm;
                padding: 4mm;
                border: 0;
                box-shadow: none;
            }
            .carton-label canvas { width: 28mm; height: 28mm; }
        }
    </style>
@endsection
