@extends('layouts.app')

@section('title', 'Live Tracking')

@section('content')
    <div class="space-y-6">
        <x-page-header title="Live tracking" description="Pantau posisi dan status armada yang sedang dalam perjalanan.">
            <x-slot:actions>
                <form method="GET" action="{{ route('logistics.tracking.index') }}" class="flex w-full gap-2 sm:w-auto">
                    <div class="w-full sm:w-64">
                        <x-input name="q" type="search" value="{{ $search }}" placeholder="Cari DO, pelanggan, armada, driver" class="w-full" aria-label="Cari armada" />
                    </div>
                    <x-button type="submit" variant="secondary" icon="search">Cari</x-button>
                </form>
            </x-slot:actions>
        </x-page-header>

        <x-kpi-grid>
            <x-stat-card label="Armada dipantau" :value="$stats['total']" icon="truck" tone="brand" />
            <x-stat-card label="Berangkat" :value="$stats['departed']" icon="route" tone="info" />
            <x-stat-card label="Dalam perjalanan" :value="$stats['in_transit']" icon="activity" tone="info" />
            <x-stat-card label="Tiba / tanpa posisi" :value="$stats['arrived'].' / '.$stats['without_position']" icon="map-pin" tone="success" />
        </x-kpi-grid>

        <div class="grid gap-6 xl:grid-cols-[1.6fr_1fr]">
            <x-card padding="p-0" title="Peta posisi terakhir" description="Klik penanda untuk melihat detail Surat Jalan">
                <div id="tracking-map" class="h-[520px] w-full bg-slate-100"></div>
            </x-card>

            <x-card padding="p-0" title="Armada aktif" description="Update posisi terakhir per Surat Jalan">
                <ul class="divide-y divide-slate-100">
                    @forelse ($orders as $order)
                        <li class="px-5 py-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <a href="{{ route('logistics.tracking.show', $order) }}" class="font-mono text-sm font-semibold text-brand-700 hover:text-brand-900">{{ $order->nomor_do }}</a>
                                    <p class="mt-1 truncate text-sm text-slate-700">{{ $order->customer->nama_pabrik }}</p>
                                    <p class="text-xs text-slate-500">{{ $order->fleet->nomor_polisi }} &middot; {{ $order->driver->nama }}</p>
                                </div>
                                <x-status-badge :status="$order->status" />
                            </div>
                            <p class="mt-2 text-xs text-slate-500">
                                @if ($order->latestTrackingEvent)
                                    Update {{ $order->latestTrackingEvent->recorded_at->diffForHumans() }}
                                    @if ($order->latestTrackingEvent->hasCoordinates())
                                        &middot; {{ number_format($order->latestTrackingEvent->latitude, 4) }}, {{ number_format($order->latestTrackingEvent->longitude, 4) }}
                                    @else
                                        &middot; tanpa koordinat
                                    @endif
                                @else
                                    Belum ada update posisi
                                @endif
                            </p>
                        </li>
                    @empty
                        <li class="px-5 py-8 text-center text-sm text-slate-500">Tidak ada armada yang sedang dalam perjalanan.</li>
                    @endforelse
                </ul>
            </x-card>
        </div>

        <script type="application/json" id="tracking-data">@json($points)</script>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/tracking-map.js')
@endpush
