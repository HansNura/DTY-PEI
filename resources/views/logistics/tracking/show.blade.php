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

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="font-mono text-2xl font-semibold tracking-tight text-slate-900">{{ $order->nomor_do }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $order->customer->nama_pabrik }} &middot; {{ $order->fleet->nomor_polisi }} &middot; {{ $order->driver->nama }}</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium ring-1 ring-inset {{ $order->status->badgeClass() }}">
                {{ $order->status->label() }}
            </span>
            <a href="{{ route('logistics.tracking.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">Kembali</a>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1.4fr_1fr]">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="text-sm font-semibold text-slate-900">Posisi terakhir</h2>
            </div>

            @if (count($point) > 0)
                <div id="tracking-map" class="h-[420px] w-full bg-slate-100"></div>
            @else
                <div class="px-5 py-10 text-center text-sm text-slate-500">
                    Belum ada koordinat GPS yang dikirim driver untuk Surat Jalan ini.
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-900">Ringkasan</h2>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Tujuan</dt><dd class="text-right font-medium text-slate-900">{{ $order->customer->nama_pabrik }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Armada</dt><dd class="font-medium text-slate-900">{{ $order->fleet->nomor_polisi }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Driver</dt><dd class="font-medium text-slate-900">{{ $order->driver->nama }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Tanggal</dt><dd class="font-medium text-slate-900">{{ $order->tanggal->translatedFormat('d M Y') }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Muatan</dt><dd class="font-medium text-slate-900">{{ $order->total_rol }} rol &middot; {{ number_format($order->total_berat_kg, 2) }} kg</dd></div>
                </dl>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-900">Riwayat perjalanan</h2>
                <ol class="mt-4 space-y-4">
                    @forelse ($order->trackingEvents as $event)
                        <li class="flex gap-3">
                            <span class="mt-1 size-2.5 shrink-0 rounded-full {{ $event->status === \App\Enums\TrackingStatus::Arrived ? 'bg-emerald-500' : 'bg-indigo-500' }}"></span>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $event->status->badgeClass() }}">
                                        {{ $event->status->label() }}
                                    </span>
                                    <span class="text-xs text-slate-500">{{ $event->recorded_at->translatedFormat('d M Y, H:i') }}</span>
                                </div>
                                @if ($event->hasCoordinates())
                                    <a href="https://www.openstreetmap.org/?mlat={{ $event->latitude }}&mlon={{ $event->longitude }}#map=15/{{ $event->latitude }}/{{ $event->longitude }}"
                                       target="_blank" rel="noopener"
                                       class="mt-1 block text-xs text-indigo-700 hover:text-indigo-900">
                                        {{ number_format($event->latitude, 5) }}, {{ number_format($event->longitude, 5) }}
                                    </a>
                                @else
                                    <p class="mt-1 text-xs text-slate-500">Tanpa koordinat</p>
                                @endif
                            </div>
                        </li>
                    @empty
                        <li class="text-sm text-slate-500">Belum ada riwayat perjalanan.</li>
                    @endforelse
                </ol>
            </div>
        </div>
    </div>

    <script type="application/json" id="tracking-data">@json($point)</script>
@endsection

@push('scripts')
    @vite('resources/js/tracking-map.js')
@endpush
