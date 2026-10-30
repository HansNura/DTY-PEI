@extends('layouts.app')

@section('title', 'Pilih DO untuk Dipindai')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Scanner Muatan</h1>
            <p class="mt-1 text-sm text-slate-500">Pilih Surat Jalan yang sedang dimuat, lalu scan QR code setiap gulungan untuk memvalidasinya.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('warehouse.scans.index') }}" class="mt-6 flex gap-3">
        <label for="q" class="sr-only">Cari nomor DO atau pelanggan</label>
        <input id="q" name="q" type="search" value="{{ $search }}" placeholder="Cari nomor DO, pelanggan, atau armada"
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
        <button type="submit" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Cari</button>
    </form>

    <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4">
            <p class="text-sm text-slate-500">Menampilkan DO dengan status <strong>Diterbitkan</strong> dan <strong>Pemuatan</strong>.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Nomor DO</th>
                        <th class="px-5 py-3 font-medium">Pelanggan</th>
                        <th class="px-5 py-3 font-medium">Armada</th>
                        <th class="px-5 py-3 font-medium">Rol Muatan</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($orders as $order)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-semibold text-slate-900">{{ $order->nomor_do }}</td>
                            <td class="px-5 py-3 text-slate-700">{{ $order->customer->nama_pabrik }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $order->fleet->nomor_polisi }}</td>
                            <td class="px-5 py-3 tabular-nums">{{ $order->items->count() }} rol</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $order->status->badgeClass() }}">
                                    {{ $order->status->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('warehouse.scans.scanner', $order) }}"
                                   class="rounded-lg bg-indigo-700 px-3.5 py-1.5 text-sm font-medium text-white transition hover:bg-indigo-800">
                                    Buka Scanner
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">Tidak ada DO aktif yang perlu dipindai saat ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($orders->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">{{ $orders->links() }}</div>
        @endif
    </div>
@endsection
