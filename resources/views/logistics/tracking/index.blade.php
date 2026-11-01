@extends('layouts.app')

@section('title', 'Live Tracking')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Live tracking</h1>
            <p class="mt-1 text-sm text-slate-500">Pantau posisi dan status armada yang sedang dalam perjalanan.</p>
        </div>
        <form method="GET" action="{{ route('logistics.tracking.index') }}" class="flex gap-2">
            <input name="q" type="search" value="{{ $search }}" placeholder="Cari DO, pelanggan, armada, driver"
                   class="w-64 rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
            <button type="submit" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Cari</button>
        </form>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Armada dipantau</p>
            <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">{{ $stats['total'] }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Berangkat</p>
            <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">{{ $stats['departed'] }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Dalam perjalanan</p>
            <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">{{ $stats['in_transit'] }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Tiba / tanpa posisi</p>
            <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">{{ $stats['arrived'] }} <span class="text-base font-normal text-slate-400">/ {{ $stats['without_position'] }}</span></p>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1.6fr_1fr]">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <h2 class="text-sm font-semibold text-slate-900">Peta posisi terakhir</h2>
                <span class="text-xs text-slate-500">Klik penanda untuk detail</span>
            </div>
            <div id="tracking-map" class="h-[480px] w-full bg-slate-100"></div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="text-sm font-semibold text-slate-900">Armada aktif</h2>
            </div>

            <ul class="divide-y divide-slate-100">
                @forelse ($orders as $order)
                    <li class="px-5 py-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <a href="{{ route('logistics.tracking.show', $order) }}" class="font-mono text-sm font-semibold text-indigo-700 hover:text-indigo-900">{{ $order->nomor_do }}</a>
                                <p class="mt-1 truncate text-sm text-slate-700">{{ $order->customer->nama_pabrik }}</p>
                                <p class="text-xs text-slate-500">{{ $order->fleet->nomor_polisi }} &middot; {{ $order->driver->nama }}</p>
                            </div>
                            <span class="shrink-0 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $order->status->badgeClass() }}">
                                {{ $order->status->label() }}
                            </span>
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
        </div>
    </div>

    <script type="application/json" id="tracking-data">@json($points)</script>
@endsection

@push('scripts')
    @vite('resources/js/tracking-map.js')
@endpush
