@extends('layouts.app')

@section('title', 'Tracking — ' . $order->nomor_do)

@section('content')
    @php
        $latest = $order->latestTrackingEvent;
        $point = $latest && $latest->hasCoordinates() ? [[
            'id' => $order->id,
            'nomor_do' => $order->nomor_do,
            'customer' => $order->customer->nama_pabrik,
            'fleet' => $order->fleet->nomor_polisi,
            'driver' => $order->driver->nama,
            'status' => $order->status->label(),
            'latitude' => $latest->latitude,
            'longitude' => $latest->longitude,
            'url' => route('logistics.tracking.show', $order),
        ]] : [];
    @endphp

    <div class="space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="font-mono text-2xl font-semibold tracking-tight text-slate-900">{{ $order->nomor_do }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $order->customer->nama_pabrik }} &middot; {{ $order->fleet->nomor_polisi }} &middot; {{ $order->driver->nama }}</p>
            </div>
            <div class="flex items-center gap-3">
                <x-status-badge :status="$order->status" />
                <x-button href="{{ route('logistics.tracking.index') }}" variant="ghost" icon="arrow-left">Kembali</x-button>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.4fr_1fr]">
            <x-card padding="p-0" title="Posisi terakhir">
                @if (count($point) > 0)
                    <div id="tracking-map" class="map-embed h-[440px] w-full bg-slate-100"></div>
                @else
                    <div class="px-5 py-12 text-center text-sm text-slate-500">
                        Belum ada koordinat GPS yang dikirim driver untuk Surat Jalan ini.
                    </div>
                @endif
            </x-card>

            <div class="space-y-6">
                <x-card title="Ringkasan">
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Tujuan</dt><dd class="text-right font-medium text-slate-900">{{ $order->customer->nama_pabrik }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Armada</dt><dd class="font-mono font-medium text-slate-900">{{ $order->fleet->nomor_polisi }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Driver</dt><dd class="font-medium text-slate-900">{{ $order->driver->nama }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Tanggal</dt><dd class="font-medium text-slate-900">{{ $order->tanggal->translatedFormat('d M Y') }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Muatan</dt><dd class="font-medium text-slate-900">{{ $order->total_rol }} rol &middot; {{ number_format($order->total_berat_kg, 2) }} kg</dd></div>
                    </dl>
                </x-card>

                <x-card title="Riwayat perjalanan">
                    <x-timeline>
                        @forelse ($order->trackingEvents as $event)
                            <x-timeline-item
                                :title="$event->status->label()"
                                :meta="$event->recorded_at->translatedFormat('d M Y, H:i')"
                                :tone="\App\Support\StatusTone::for($event->status)"
                            >
                                @if ($event->hasCoordinates())
                                    <a href="https://www.openstreetmap.org/?mlat={{ $event->latitude }}&mlon={{ $event->longitude }}#map=15/{{ $event->latitude }}/{{ $event->longitude }}"
                                       target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-xs text-brand-700 hover:text-brand-900">
                                        <x-icon name="map-pin" class="size-3.5" />
                                        {{ number_format($event->latitude, 5) }}, {{ number_format($event->longitude, 5) }}
                                    </a>
                                @else
                                    <span class="text-xs text-slate-500">Tanpa koordinat</span>
                                @endif
                            </x-timeline-item>
                        @empty
                            <li class="text-sm text-slate-500">Belum ada riwayat perjalanan.</li>
                        @endforelse
                    </x-timeline>
                </x-card>
            </div>
        </div>

        <script type="application/json" id="tracking-data">@json($point)</script>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/tracking-map.js')
@endpush
