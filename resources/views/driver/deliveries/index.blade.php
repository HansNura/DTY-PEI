@extends('layouts.app')

@section('title', 'Perjalanan Saya')

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Perjalanan saya</h1>
            <p class="mt-1 text-sm text-slate-500">Daftar Surat Jalan yang ditugaskan kepada Anda.</p>
        </div>

        <form method="GET" action="{{ route('driver.deliveries.index') }}" class="flex flex-wrap gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <select name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                <option value="">Semua status</option>
                @foreach ($statuses as $statusOption)
                    <option value="{{ $statusOption->value }}" @selected($status === $statusOption->value)>{{ $statusOption->label() }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Filter</button>
            <a href="{{ route('driver.deliveries.index') }}" class="rounded-lg px-3 py-2 text-sm text-slate-500 hover:text-slate-800">Reset</a>
        </form>

        <div class="space-y-4">
            @forelse ($orders as $order)
                <a href="{{ route('driver.deliveries.show', $order) }}" class="block rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 hover:shadow">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="font-mono text-sm font-semibold text-slate-900">{{ $order->nomor_do }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ $order->customer->nama_pabrik }}</p>
                            <p class="text-xs text-slate-500">{{ $order->fleet->nomor_polisi }} &middot; {{ $order->tanggal->translatedFormat('d M Y') }}</p>
                        </div>
                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $order->status->badgeClass() }}">
                            {{ $order->status->label() }}
                        </span>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm">
                        <span class="text-slate-600">Muatan: <span class="font-semibold text-slate-900">{{ $order->loaded_count }} / {{ $order->total_rol }}</span> rol</span>
                        <span class="text-slate-500">
                            @if ($order->latestTrackingEvent)
                                Update terakhir: {{ $order->latestTrackingEvent->recorded_at->translatedFormat('d M Y, H:i') }}
                            @else
                                Belum ada update perjalanan
                            @endif
                        </span>
                    </div>
                </a>
            @empty
                <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500">
                    Belum ada Surat Jalan yang ditugaskan kepada Anda.
                </div>
            @endforelse
        </div>

        @if ($orders->hasPages())
            <div>{{ $orders->links() }}</div>
        @endif
    </div>
@endsection
