@extends('layouts.app')

@section('title', 'Surat Jalan')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Surat Jalan</h1>
            <p class="mt-1 text-sm text-slate-500">Kelola dan pantau seluruh pengiriman rol benang DTY.</p>
        </div>
        <a href="{{ route('logistics.orders.create') }}" class="rounded-lg bg-indigo-700 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-indigo-800">
            Terbitkan Surat Jalan
        </a>
    </div>

    <form method="GET" action="{{ route('logistics.orders.index') }}" class="mt-6 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-[1.6fr_1fr_auto]">
        <label for="q" class="sr-only">Cari nomor DO atau pelanggan</label>
        <input id="q" name="q" type="search" value="{{ $search }}" placeholder="Cari nomor DO, pelanggan, atau armada"
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
        <label for="status" class="sr-only">Status</label>
        <select id="status" name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
            <option value="">Semua status</option>
            @foreach ($statuses as $s)
                <option value="{{ $s->value }}" @selected($status === $s->value)>{{ $s->label() }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <button type="submit" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Filter</button>
            <a href="{{ route('logistics.orders.index') }}" class="rounded-lg px-3 py-2 text-sm text-slate-500 hover:text-slate-800">Reset</a>
        </div>
    </form>

    <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Nomor DO / Tanggal</th>
                        <th class="px-5 py-3 font-medium">Pelanggan</th>
                        <th class="px-5 py-3 font-medium">Armada / Driver</th>
                        <th class="px-5 py-3 font-medium">Rol</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($orders as $order)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <p class="font-semibold text-slate-900">{{ $order->nomor_do }}</p>
                                <p class="text-xs text-slate-500">{{ $order->tanggal->format('d M Y') }}</p>
                            </td>
                            <td class="px-5 py-3">
                                <p class="font-medium text-slate-800">{{ $order->customer->nama_pabrik }}</p>
                                <p class="text-xs text-slate-500 truncate max-w-[180px]">{{ $order->customer->alamat }}</p>
                            </td>
                            <td class="px-5 py-3">
                                <p class="font-medium text-slate-800">{{ $order->fleet->nomor_polisi }}</p>
                                <p class="text-xs text-slate-500">{{ $order->driver->nama }}</p>
                            </td>
                            <td class="px-5 py-3 tabular-nums">
                                {{ $order->items->count() }} rol
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $order->status->badgeClass() }}">
                                    {{ $order->status->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('logistics.orders.show', $order) }}" class="text-sm font-medium text-indigo-700 hover:text-indigo-900">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">Belum ada surat jalan yang cocok dengan filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($orders->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">{{ $orders->links() }}</div>
        @endif
    </div>
@endsection
