@extends('layouts.app')

@section('title', 'Rekap Pengiriman')

@section('content')
    <div class="space-y-6">
        <x-page-header title="Rekap pengiriman" description="Filter berdasarkan periode, nomor lot, atau armada, lalu unduh PDF atau Excel.">
            <x-slot:actions>
                <x-button href="{{ route('logistics.reports.deliveries.pdf', request()->query()) }}" variant="secondary" icon="download">Unduh PDF</x-button>
                <x-button href="{{ route('logistics.reports.deliveries.excel', request()->query()) }}" variant="secondary" icon="download">Unduh Excel</x-button>
            </x-slot:actions>
        </x-page-header>

        <x-card padding="p-4">
            <form method="GET" action="{{ route('logistics.reports.deliveries') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_1fr_auto]">
                <x-field label="Tanggal awal" name="from">
                    <x-input id="from" name="from" type="date" value="{{ $filters['from'] ?? '' }}" />
                </x-field>

                <x-field label="Tanggal akhir" name="to">
                    <x-input id="to" name="to" type="date" value="{{ $filters['to'] ?? '' }}" />
                </x-field>

                <x-field label="Nomor lot" name="lot">
                    <x-input id="lot" name="lot" type="search" maxlength="50" value="{{ $filters['lot'] ?? '' }}" placeholder="Contoh: LOT-2026-001" />
                </x-field>

                <x-field label="Armada" name="fleet_id">
                    <x-select id="fleet_id" name="fleet_id">
                        <option value="">Semua armada</option>
                        @foreach ($fleets as $fleet)
                            <option value="{{ $fleet->id }}" @selected(($filters['fleet_id'] ?? '') == $fleet->id)>{{ $fleet->nomor_polisi }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <div class="flex items-end gap-2">
                    <x-button type="submit" icon="filter">Terapkan</x-button>
                    <x-button href="{{ route('logistics.reports.deliveries') }}" variant="ghost">Reset</x-button>
                </div>
            </form>
        </x-card>

        <p class="text-sm text-slate-500">{{ $orders->count() }} surat jalan ditemukan.</p>

        <x-table class="hidden md:block" padding="p-0">
            <thead class="bg-slate-50/70">
                <tr>
                    <x-th>Nomor / tanggal DO</x-th>
                    <x-th>Pelanggan</x-th>
                    <x-th>Armada</x-th>
                    <x-th>Lot</x-th>
                    <x-th>Rol / berat</x-th>
                    <x-th>Status / penerima</x-th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($orders as $order)
                    <tr class="hover:bg-slate-50/60">
                        <x-td>
                            <p class="font-mono text-xs font-semibold text-slate-900">{{ $order->nomor_do }}</p>
                            <p class="text-xs text-slate-500">{{ $order->tanggal->translatedFormat('d M Y') }}</p>
                        </x-td>
                        <x-td>{{ $order->customer->nama_pabrik }}</x-td>
                        <x-td>
                            <p class="font-medium text-slate-800">{{ $order->fleet->nomor_polisi }}</p>
                            <p class="text-xs text-slate-500">{{ $order->driver->nama }}</p>
                        </x-td>
                        <x-td>{{ $order->items->pluck('roll.nomor_lot')->filter()->unique()->implode(', ') }}</x-td>
                        <x-td class="tabular-nums">{{ $order->items->count() }} rol / {{ str_replace('.', ',', $order->totalWeightInKg()) }} kg</x-td>
                        <x-td>
                            <div class="flex flex-col items-start gap-1">
                                <x-status-badge :status="$order->status" />
                                <span class="text-xs text-slate-500">{{ $order->proofOfDelivery?->received_by_name ?? '—' }}</span>
                            </div>
                        </x-td>
                    </tr>
                @empty
                    <tr><x-td colspan="6" class="py-8 text-center text-slate-500">Tidak ada pengiriman yang sesuai filter.</x-td></tr>
                @endforelse
            </tbody>
        </x-table>

        <div class="space-y-3 md:hidden">
            @forelse ($orders as $order)
                <x-card padding="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-mono text-xs font-semibold text-slate-900">{{ $order->nomor_do }}</p>
                            <p class="truncate text-sm text-slate-700">{{ $order->customer->nama_pabrik }}</p>
                            <p class="text-xs text-slate-500">{{ $order->fleet->nomor_polisi }} · {{ $order->tanggal->translatedFormat('d M Y') }}</p>
                        </div>
                        <x-status-badge :status="$order->status" />
                    </div>
                    <dl class="mt-3 grid grid-cols-2 gap-2 text-xs">
                        <div><dt class="text-slate-500">Lot</dt><dd class="font-medium text-slate-800">{{ $order->items->pluck('roll.nomor_lot')->filter()->unique()->implode(', ') }}</dd></div>
                        <div><dt class="text-slate-500">Muatan</dt><dd class="font-medium text-slate-800">{{ $order->items->count() }} rol / {{ str_replace('.', ',', $order->totalWeightInKg()) }} kg</dd></div>
                        <div class="col-span-2"><dt class="text-slate-500">Penerima</dt><dd class="font-medium text-slate-800">{{ $order->proofOfDelivery?->received_by_name ?? '—' }}</dd></div>
                    </dl>
                </x-card>
            @empty
                <x-empty-state icon="clipboard" title="Tidak ada data" description="Tidak ada pengiriman yang sesuai filter." />
            @endforelse
        </div>
    </div>
@endsection
