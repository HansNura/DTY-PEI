@extends('layouts.app')

@section('title', 'Scanner Muatan')

@section('content')
    <div class="space-y-6">
        <x-page-header title="Scanner muatan" description="Pilih Surat Jalan yang sedang dimuat, lalu pindai QR setiap gulungan untuk memvalidasinya." />

        <x-card padding="p-4">
            <form method="GET" action="{{ route('warehouse.scans.index') }}" class="flex flex-col gap-3 sm:flex-row">
                <x-input id="q" name="q" type="search" value="{{ $search }}" placeholder="Cari nomor DO, pelanggan, atau armada" aria-label="Cari surat jalan" class="flex-1" />
                <x-button type="submit" variant="secondary" icon="search">Cari</x-button>
            </form>
        </x-card>

        @if ($orders->isEmpty())
            <x-empty-state icon="scan" title="Tidak ada DO untuk dipindai" description="Surat Jalan yang berstatus Diterbitkan atau Pemuatan akan muncul di sini." />
        @else
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($orders as $order)
                    @php $total = $order->items->count(); $loaded = $order->loaded_count; @endphp
                    <x-card class="flex flex-col">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-mono text-sm font-semibold text-slate-900">{{ $order->nomor_do }}</p>
                                <p class="mt-1 truncate text-sm text-slate-600">{{ $order->customer->nama_pabrik }}</p>
                                <p class="text-xs text-slate-500">{{ $order->fleet->nomor_polisi }} · {{ $order->driver->nama }}</p>
                            </div>
                            <x-status-badge :status="$order->status" />
                        </div>

                        <div class="mt-4">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-500">Progress muatan</span>
                                <span class="font-semibold tabular-nums text-slate-800">{{ $loaded }}/{{ $total }} rol</span>
                            </div>
                            <x-progress :value="$loaded" :max="$total" class="mt-2" />
                        </div>

                        <div class="mt-5 flex items-center justify-between gap-3 border-t border-slate-100 pt-4">
                            <span class="text-xs text-slate-500">{{ $order->tanggal->translatedFormat('d M Y') }}</span>
                            <x-button href="{{ route('warehouse.scans.scanner', $order) }}" size="sm" icon="scan">Buka scanner</x-button>
                        </div>
                    </x-card>
                @endforeach
            </div>

            <x-pagination :paginator="$orders" />
        @endif
    </div>
@endsection
