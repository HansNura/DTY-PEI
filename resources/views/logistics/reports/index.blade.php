@extends('layouts.app')

@section('title', 'Rekap pengiriman')

@section('content')
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Rekap pengiriman</h1>
        <p class="mt-1 text-sm text-slate-500">Filter berdasarkan periode, nomor lot, atau armada; lalu unduh PDF atau Excel.</p>
    </div>

    <form method="GET" action="{{ route('logistics.reports.deliveries') }}" class="mt-6 grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:grid-cols-2 lg:grid-cols-5">
        <div>
            <label for="from" class="text-sm font-medium text-slate-700">Tanggal awal</label>
            <input id="from" name="from" type="date" value="{{ $filters['from'] ?? '' }}" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
        </div>
        <div>
            <label for="to" class="text-sm font-medium text-slate-700">Tanggal akhir</label>
            <input id="to" name="to" type="date" value="{{ $filters['to'] ?? '' }}" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
        </div>
        <div>
            <label for="lot" class="text-sm font-medium text-slate-700">Nomor lot</label>
            <input id="lot" name="lot" type="search" maxlength="50" value="{{ $filters['lot'] ?? '' }}" placeholder="Contoh: LOT-2026-001" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
        </div>
        <div>
            <label for="fleet_id" class="text-sm font-medium text-slate-700">Armada</label>
            <select id="fleet_id" name="fleet_id" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                <option value="">Semua armada</option>
                @foreach ($fleets as $fleet)
                    <option value="{{ $fleet->id }}" @selected(($filters['fleet_id'] ?? '') == $fleet->id)>{{ $fleet->nomor_polisi }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="rounded-lg bg-indigo-700 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-indigo-800">Terapkan</button>
            <a href="{{ route('logistics.reports.deliveries') }}" class="rounded-lg px-3 py-2.5 text-sm text-slate-500 hover:text-slate-800">Reset</a>
        </div>
        @foreach ($errors->all() as $error)
            <p class="text-sm text-rose-700 sm:col-span-2 lg:col-span-5">{{ $error }}</p>
        @endforeach
    </form>

    <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500">{{ $orders->count() }} surat jalan ditemukan.</p>
        <div class="flex gap-2">
            <a href="{{ route('logistics.reports.deliveries.pdf', request()->query()) }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Unduh PDF</a>
            <a href="{{ route('logistics.reports.deliveries.excel', request()->query()) }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Unduh Excel</a>
        </div>
    </div>

    <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr><th class="px-5 py-3 font-medium">Nomor / tanggal DO</th><th class="px-5 py-3 font-medium">Pelanggan</th><th class="px-5 py-3 font-medium">Armada</th><th class="px-5 py-3 font-medium">Lot</th><th class="px-5 py-3 font-medium">Rol / berat</th><th class="px-5 py-3 font-medium">Status / penerima</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($orders as $order)
                        <tr>
                            <td class="px-5 py-3"><p class="font-medium">{{ $order->nomor_do }}</p><p class="text-xs text-slate-500">{{ $order->tanggal->format('d/m/Y') }}</p></td>
                            <td class="px-5 py-3">{{ $order->customer->nama_pabrik }}</td>
                            <td class="px-5 py-3">{{ $order->fleet->nomor_polisi }}</td>
                            <td class="px-5 py-3">{{ $order->items->pluck('roll.nomor_lot')->filter()->unique()->implode(', ') }}</td>
                            <td class="px-5 py-3">{{ $order->items->count() }} / {{ str_replace('.', ',', $order->totalWeightInKg()) }} kg</td>
                            <td class="px-5 py-3"><p>{{ $order->status->label() }}</p><p class="text-xs text-slate-500">{{ $order->proofOfDelivery?->received_by_name ?? '—' }}</p></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">Tidak ada pengiriman yang sesuai filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
