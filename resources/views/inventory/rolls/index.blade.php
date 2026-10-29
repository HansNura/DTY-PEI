@extends('layouts.app')

@section('title', 'Gulungan DTY')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Stok gulungan DTY</h1>
            <p class="mt-1 text-sm text-slate-500">Catat spesifikasi, nomor lot, dan cetak label QR untuk tiap gulungan.</p>
        </div>
        <a href="{{ route('warehouse.rolls.create') }}" class="rounded-lg bg-indigo-700 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-indigo-800">
            Tambah gulungan
        </a>
    </div>

    <form method="GET" action="{{ route('warehouse.rolls.index') }}" class="mt-6 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-[1.6fr_1fr_auto]">
        <label for="q" class="sr-only">Cari kode rol atau nomor lot</label>
        <input id="q" name="q" type="search" value="{{ $search }}" placeholder="Cari kode rol atau nomor lot"
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
        <label for="status" class="sr-only">Status</label>
        <select id="status" name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
            <option value="">Semua status</option>
            @foreach ($statuses as $statusOption)
                <option value="{{ $statusOption->value }}" @selected($status === $statusOption->value)>{{ $statusOption->label() }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <button type="submit" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Filter</button>
            <a href="{{ route('warehouse.rolls.index') }}" class="rounded-lg px-3 py-2 text-sm text-slate-500 hover:text-slate-800">Reset</a>
        </div>
    </form>

    <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Kode rol / Lot</th>
                        <th class="px-5 py-3 font-medium">Spesifikasi</th>
                        <th class="px-5 py-3 font-medium">Berat</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($rolls as $roll)
                        <tr>
                            <td class="px-5 py-3">
                                <p class="font-medium text-slate-900">{{ $roll->kode_rol }}</p>
                                <p class="text-xs text-slate-500">Lot {{ $roll->nomor_lot }}</p>
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ number_format($roll->denier) }} D / {{ number_format($roll->filamen) }} filamen</td>
                            <td class="px-5 py-3 text-slate-600">{{ number_format((float) $roll->berat_gram, 2, ',', '.') }} gram</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $roll->status === \App\Enums\RollStatus::InStock ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-slate-50 text-slate-600 ring-slate-200' }}">
                                    {{ $roll->status->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-3">
                                    @if ($roll->status !== \App\Enums\RollStatus::Cancelled)
                                        <a href="{{ route('warehouse.rolls.label', $roll) }}" class="text-sm font-medium text-indigo-700 hover:text-indigo-900">Cetak label</a>
                                    @endif
                                    @if ($roll->status === \App\Enums\RollStatus::InStock)
                                        <a href="{{ route('warehouse.rolls.edit', $roll) }}" class="text-sm font-medium text-slate-700 hover:text-slate-900">Edit</a>
                                        <form method="POST" action="{{ route('warehouse.rolls.cancel', $roll) }}"
                                              x-data
                                              x-on:submit="if (! confirm('Batalkan gulungan {{ $roll->kode_rol }}? Riwayat tidak akan dihapus.')) $event.preventDefault()">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-sm font-medium text-rose-700 hover:text-rose-900">Batalkan</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-slate-500">Belum ada gulungan yang cocok dengan filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($rolls->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">{{ $rolls->links() }}</div>
        @endif
    </div>
@endsection
