@extends('layouts.app')

@section('title', 'Perjalanan Saya')

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <x-page-header title="Perjalanan saya" description="Daftar Surat Jalan yang ditugaskan kepada Anda." />

        <x-card padding="p-4">
            <form method="GET" action="{{ route('driver.deliveries.index') }}" class="flex flex-wrap items-end gap-3">
                <x-field name="status" class="min-w-48 flex-1">
                    <x-select name="status" aria-label="Filter status">
                        <option value="">Semua status</option>
                        @foreach ($statuses as $statusOption)
                            <option value="{{ $statusOption->value }}" @selected($status === $statusOption->value)>{{ $statusOption->label() }}</option>
                        @endforeach
                    </x-select>
                </x-field>
                <x-button type="submit" variant="secondary" icon="filter">Filter</x-button>
                <x-button href="{{ route('driver.deliveries.index') }}" variant="ghost">Reset</x-button>
            </form>
        </x-card>

        <div class="space-y-4">
            @forelse ($orders as $order)
                <a href="{{ route('driver.deliveries.show', $order) }}" class="block rounded-2xl border border-slate-200/80 bg-white p-5 shadow-card transition hover:border-brand-300 hover:shadow-card-hover">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-mono text-sm font-semibold text-slate-900">{{ $order->nomor_do }}</p>
                            <p class="mt-1 truncate text-sm text-slate-700">{{ $order->customer->nama_pabrik }}</p>
                            <p class="text-xs text-slate-500">{{ $order->fleet->nomor_polisi }} &middot; {{ $order->tanggal->translatedFormat('d M Y') }}</p>
                        </div>
                        <x-status-badge :status="$order->status" />
                    </div>

                    <div class="mt-4">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-500">Muatan</span>
                            <span class="font-semibold tabular-nums text-slate-800">{{ $order->loaded_count }} / {{ $order->total_rol }} rol</span>
                        </div>
                        <x-progress :value="$order->loaded_count" :max="$order->total_rol" class="mt-2" />
                    </div>

                    <div class="mt-4 flex items-center justify-between gap-3 border-t border-slate-100 pt-3 text-xs text-slate-500">
                        <span>
                            @if ($order->latestTrackingEvent)
                                Update {{ $order->latestTrackingEvent->recorded_at->translatedFormat('d M Y, H:i') }}
                            @else
                                Belum ada update perjalanan
                            @endif
                        </span>
                        <span class="inline-flex items-center gap-1 font-medium text-brand-700">Buka <x-icon name="chevron-right" class="size-3.5" /></span>
                    </div>
                </a>
            @empty
                <x-empty-state icon="truck" title="Belum ada perjalanan" description="Surat Jalan yang ditugaskan kepada Anda akan muncul di sini." />
            @endforelse
        </div>

        <x-pagination :paginator="$orders" />
    </div>
@endsection
