@extends('layouts.app')

@section('title', 'Detail — ' . $order->nomor_do)

@section('content')
    <div class="mx-auto max-w-4xl space-y-6">

        {{-- Header --}}
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-semibold tracking-tight text-slate-900">{{ $order->nomor_do }}</h1>
                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $order->status->badgeClass() }}">
                        {{ $order->status->label() }}
                    </span>
                </div>
                <p class="mt-1 text-sm text-slate-500">
                    Diterbitkan oleh {{ $order->creator->nama }} pada {{ $order->created_at->format('d M Y, H:i') }}
                </p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('logistics.orders.print', $order) }}" target="_blank"
                   class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                    Cetak DO
                </a>
                @if (in_array($order->status->value, ['issued', 'loading', 'draft']))
                    <form method="POST" action="{{ route('logistics.orders.cancel', $order) }}"
                          x-data x-on:submit="if (!confirm('Batalkan surat jalan {{ $order->nomor_do }}? Semua rol akan dikembalikan ke stok.')) $event.preventDefault()">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-rose-700">
                            Batalkan DO
                        </button>
                    </form>
                @endif
                <a href="{{ route('logistics.orders.index') }}" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900">Kembali</a>
            </div>
        </div>

        {{-- Info Card --}}
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Pelanggan Tujuan</p>
                <p class="mt-2 font-semibold text-slate-900">{{ $order->customer->nama_pabrik }}</p>
                <p class="mt-0.5 text-sm text-slate-600">{{ $order->customer->alamat }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ $order->customer->kontak_nama }} · {{ $order->customer->kontak_telepon }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Armada &amp; Driver</p>
                <p class="mt-2 font-semibold text-slate-900">{{ $order->fleet->nomor_polisi }}</p>
                <p class="mt-0.5 text-sm text-slate-600">{{ $order->fleet->jenis_kendaraan }}</p>
                <p class="mt-1 text-sm text-slate-500">Driver: {{ $order->driver->nama }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Ringkasan Muatan</p>
                <p class="mt-2 text-3xl font-bold tabular-nums text-slate-900">{{ $order->total_rol }}</p>
                <p class="text-sm text-slate-500">rol benang DTY</p>
                <p class="mt-1 text-sm font-medium text-slate-700">
                    Total: {{ number_format($order->total_berat_kg, 2) }} kg
                </p>
            </div>
        </div>

        @if ($order->catatan)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                <span class="font-medium">Catatan:</span> {{ $order->catatan }}
            </div>
        @endif

        {{-- Daftar Rol --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-4">
                <h2 class="text-base font-semibold text-slate-800">Gulungan DTY yang Dialokasikan</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">Kode Rol</th>
                            <th class="px-5 py-3 font-medium">Nomor Lot</th>
                            <th class="px-5 py-3 font-medium">Spesifikasi</th>
                            <th class="px-5 py-3 font-medium text-right">Berat (kg)</th>
                            <th class="px-5 py-3 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($order->items as $item)
                            <tr>
                                <td class="px-5 py-3 font-mono text-xs font-semibold text-slate-800">{{ $item->dtyRoll->kode_rol }}</td>
                                <td class="px-5 py-3 text-slate-700">{{ $item->dtyRoll->nomor_lot }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $item->dtyRoll->denier }}D / {{ $item->dtyRoll->filamen }}F</td>
                                <td class="px-5 py-3 text-right tabular-nums">{{ number_format($item->berat_kg, 2) }}</td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset
                                        {{ $item->dtyRoll->status->value === 'loaded' ? 'bg-blue-50 text-blue-700 ring-blue-200' :
                                           ($item->dtyRoll->status->value === 'allocated' ? 'bg-amber-50 text-amber-700 ring-amber-200' : 'bg-slate-50 text-slate-600 ring-slate-200') }}">
                                        {{ $item->dtyRoll->status->label() }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Riwayat Scan --}}
        @if ($order->loadScans->isNotEmpty())
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h2 class="text-base font-semibold text-slate-800">Riwayat Scan Muatan</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-5 py-3 font-medium">Waktu Scan</th>
                                <th class="px-5 py-3 font-medium">Kode Rol</th>
                                <th class="px-5 py-3 font-medium">Hasil</th>
                                <th class="px-5 py-3 font-medium">Petugas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($order->loadScans->sortByDesc('scanned_at') as $scan)
                                <tr>
                                    <td class="px-5 py-3 text-xs text-slate-500 tabular-nums">{{ $scan->scanned_at->format('d M Y H:i:s') }}</td>
                                    <td class="px-5 py-3 font-mono text-xs text-slate-700">{{ $scan->dtyRoll?->kode_rol ?? '—' }}</td>
                                    <td class="px-5 py-3">
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $scan->result->badgeClass() }}">
                                            {{ $scan->result->label() }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-slate-700">{{ $scan->scanner->nama }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    </div>
@endsection
