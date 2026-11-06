@extends('layouts.app')

@section('title', 'Detail — ' . $order->nomor_do)

@section('content')
    @php
        $palletGroups = $order->palletGroups();
        $boxCount = (int) collect($palletGroups)->sum('box_count');
        $palletCount = count($palletGroups);
    @endphp

    <div class="mx-auto max-w-5xl space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="font-mono text-2xl font-semibold tracking-tight text-slate-900">{{ $order->nomor_do }}</h1>
                    <x-status-badge :status="$order->status" />
                </div>
                <p class="mt-1 text-sm text-slate-500">Diterbitkan oleh {{ $order->creator->nama }} pada {{ $order->created_at->translatedFormat('d M Y, H:i') }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <x-button href="{{ route('logistics.orders.print', $order) }}" target="_blank" variant="secondary" icon="printer">Cetak DO</x-button>
                <x-button href="{{ route('logistics.orders.pdf', $order) }}" variant="secondary" icon="download">Unduh PDF</x-button>
                @if (in_array($order->status->value, ['issued', 'loading', 'draft']))
                    <x-confirm-button
                        :action="route('logistics.orders.cancel', $order)"
                        method="PATCH"
                        title="Batalkan Surat Jalan?"
                        :description="'Semua rol pada '.$order->nomor_do.' akan dikembalikan ke stok gudang.'"
                        confirm-label="Ya, batalkan DO"
                    >
                        Batalkan DO
                    </x-confirm-button>
                @endif
                <x-button href="{{ route('logistics.orders.index') }}" variant="ghost" icon="arrow-left">Kembali</x-button>
            </div>
        </div>

        <x-card>
            <x-status-stepper :status="$order->status" />
        </x-card>

        <div class="grid gap-4 sm:grid-cols-3">
            <x-card title="Pelanggan tujuan">
                <p class="font-semibold text-slate-900">{{ $order->customer->nama_pabrik }}</p>
                <p class="mt-1 text-sm text-slate-600">{{ $order->customer->alamat }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ $order->customer->kontak_nama }} · {{ $order->customer->kontak_telepon }}</p>
            </x-card>

            <x-card title="Armada & driver">
                <p class="font-mono font-semibold text-slate-900">{{ $order->fleet->nomor_polisi }}</p>
                <p class="mt-1 text-sm text-slate-600">{{ $order->fleet->jenis_kendaraan }}</p>
                <p class="mt-2 text-sm text-slate-500">Driver: {{ $order->driver->nama }}</p>
            </x-card>

            <x-card title="Ringkasan muatan">
                <p class="text-3xl font-semibold tabular-nums text-slate-900">{{ $order->total_rol }}</p>
                <p class="text-sm text-slate-500">bobbin DTY</p>
                <p class="mt-1 text-sm text-slate-600">{{ $boxCount }} kardus · {{ $palletCount }} pallet</p>
                <p class="mt-2 text-sm font-medium text-slate-700">Total {{ number_format($order->total_berat_kg, 2) }} kg netto</p>
            </x-card>
        </div>

        @if ($order->catatan)
            <x-alert tone="warning" title="Catatan">
                {{ $order->catatan }}
            </x-alert>
        @endif

        @if ($palletGroups !== [])
            <x-card title="Palletisasi pengiriman" description="Pallet dibentuk otomatis dengan kapasitas maksimal 30 kardus per pallet.">
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($palletGroups as $pallet)
                        <div class="rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-3">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-slate-800">Pallet {{ $pallet['number'] }}</p>
                                <x-badge :tone="$pallet['box_count'] < 30 ? 'warning' : 'neutral'">{{ $pallet['box_count'] }}/30 kardus</x-badge>
                            </div>
                            <p class="mt-1 text-xs text-slate-600">{{ $pallet['bobbin_count'] }} bobbin · <span class="font-semibold tabular-nums">{{ number_format($pallet['net_kg'], 2, ',', '.') }} kg netto</span></p>
                            <p class="mt-1 line-clamp-2 text-[10px] text-slate-400">{{ $pallet['cartons']->pluck('carton.nomor_hu')->join(', ') }}</p>
                        </div>
                    @endforeach
                </div>
            </x-card>
        @endif

        @if ($order->proofOfDelivery)
            <x-card title="Bukti penerimaan digital">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium text-slate-900">Diterima oleh {{ $order->proofOfDelivery->received_by_name }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $order->proofOfDelivery->received_at->translatedFormat('d M Y, H:i') }}</p>
                    </div>
                    <x-badge tone="success" dot>POD tersimpan</x-badge>
                </div>
                <img src="{{ route('deliveries.signature', $order) }}" alt="Tanda tangan penerima {{ $order->proofOfDelivery->received_by_name }}"
                     class="mt-4 max-h-32 rounded-xl border border-slate-200 bg-white p-2">
            </x-card>
        @endif

        @if ($order->trackingEvents->isNotEmpty())
            <x-card title="Riwayat perjalanan">
                <x-timeline>
                    @foreach ($order->trackingEvents as $event)
                        <x-timeline-item
                            :title="$event->status->label()"
                            :meta="$event->recorded_at->translatedFormat('d M Y, H:i')"
                            :tone="\App\Support\StatusTone::for($event->status)"
                        >
                            @if ($event->hasCoordinates())
                                <a href="https://www.openstreetmap.org/?mlat={{ $event->latitude }}&mlon={{ $event->longitude }}#map=15/{{ $event->latitude }}/{{ $event->longitude }}"
                                   target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-xs text-brand-700">
                                    <x-icon name="map-pin" class="size-3.5" />
                                    {{ number_format($event->latitude, 5) }}, {{ number_format($event->longitude, 5) }}
                                </a>
                            @else
                                <span class="text-xs text-slate-500">Tanpa koordinat</span>
                            @endif
                        </x-timeline-item>
                    @endforeach
                </x-timeline>
            </x-card>
        @endif

        <x-card padding="p-0" title="Gulungan DTY yang dialokasikan">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50/70">
                        <tr>
                            <x-th>Nomor seri</x-th>
                            <x-th>Kardus</x-th>
                            <x-th>Nomor lot</x-th>
                            <x-th>Spesifikasi</x-th>
                            <x-th align="right">Berat (kg)</x-th>
                            <x-th>Status</x-th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($order->items as $item)
                            <tr>
                                <x-td><span class="font-mono text-xs font-semibold text-slate-800">{{ $item->dtyRoll->nomor_seri ?? '—' }}</span></x-td>
                                <x-td>
                                    <span class="font-mono text-xs text-slate-700">{{ $item->dtyRoll->carton?->nomor_hu ?? '—' }}</span>
                                    @if ($item->dtyRoll->carton)
                                        <p class="text-[11px] text-slate-500">Merge {{ $item->dtyRoll->carton->nomor_merge }}</p>
                                    @endif
                                </x-td>
                                <x-td>{{ $item->dtyRoll->nomor_lot }}</x-td>
                                <x-td class="tabular-nums">{{ $item->dtyRoll->denier }}D / {{ $item->dtyRoll->filamen }}F</x-td>
                                <x-td align="right" class="tabular-nums">{{ number_format($item->berat_kg, 2) }}</x-td>
                                <x-td><x-status-badge :status="$item->dtyRoll->status" /></x-td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>

        @if ($order->loadScans->isNotEmpty())
            <x-card title="Riwayat scan muatan" description="Validasi QR yang dilakukan checker gudang">
                <x-timeline>
                    @foreach ($order->loadScans->sortByDesc('scanned_at') as $scan)
                        <x-timeline-item
                            :title="$scan->dtyCarton?->nomor_hu ?? $scan->dtyRoll?->nomor_seri ?? 'Kode tidak dikenal'"
                            :meta="$scan->scanned_at->translatedFormat('d M Y, H:i:s').' · '.$scan->scanner->nama"
                            :tone="\App\Support\StatusTone::for($scan->result)"
                        >
                            <div class="flex flex-wrap items-center gap-2">
                                <x-status-badge :status="$scan->result" />
                                @if ($scan->dtyCarton)
                                    <span class="text-xs text-slate-500">Merge {{ $scan->dtyCarton->nomor_merge }} · {{ $scan->dtyCarton->unit_bobbins }} bobbin</span>
                                @elseif ($scan->dtyRoll)
                                    <span class="text-xs text-slate-500">Lot {{ $scan->dtyRoll->nomor_lot }}</span>
                                @endif
                            </div>
                        </x-timeline-item>
                    @endforeach
                </x-timeline>
            </x-card>
        @endif
    </div>
@endsection
