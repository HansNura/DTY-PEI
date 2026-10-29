@extends('layouts.app')

@section('title', 'Armada')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Master armada</h1>
            <p class="mt-1 text-sm text-slate-500">Kelola kendaraan, kapasitas, dan pengemudi utama.</p>
        </div>
        <a href="{{ route('logistics.fleets.create') }}" class="rounded-lg bg-indigo-700 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-indigo-800">Tambah armada</a>
    </div>

    <form method="GET" action="{{ route('logistics.fleets.index') }}" class="mt-6 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-[1.6fr_1fr_auto]">
        <label for="q" class="sr-only">Cari nomor polisi, kendaraan, atau pengemudi</label>
        <input id="q" name="q" type="search" value="{{ $search }}" placeholder="Cari nomor polisi, kendaraan, pengemudi"
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
        <label for="status" class="sr-only">Status</label>
        <select id="status" name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
            <option value="">Semua status</option>
            <option value="active" @selected($status === 'active')>Aktif</option>
            <option value="inactive" @selected($status === 'inactive')>Nonaktif</option>
        </select>
        <div class="flex gap-2">
            <button type="submit" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Filter</button>
            <a href="{{ route('logistics.fleets.index') }}" class="rounded-lg px-3 py-2 text-sm text-slate-500 hover:text-slate-800">Reset</a>
        </div>
    </form>

    <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Nomor polisi</th>
                        <th class="px-5 py-3 font-medium">Jenis kendaraan</th>
                        <th class="px-5 py-3 font-medium">Kapasitas</th>
                        <th class="px-5 py-3 font-medium">Pengemudi</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($fleets as $fleet)
                        <tr>
                            <td class="px-5 py-3 font-medium text-slate-900">{{ $fleet->nomor_polisi }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $fleet->jenis_kendaraan }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ number_format((float) $fleet->kapasitas_kg, 2, ',', '.') }} kg</td>
                            <td class="px-5 py-3 text-slate-600">{{ $fleet->driver?->nama ?? 'Belum ditentukan' }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $fleet->is_active ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-rose-50 text-rose-700 ring-rose-200' }}">{{ $fleet->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('logistics.fleets.edit', $fleet) }}" class="text-sm font-medium text-indigo-700 hover:text-indigo-900">Edit</a>
                                    <form method="POST" action="{{ route('logistics.fleets.toggle-active', $fleet) }}" x-data x-on:submit="if (! confirm('{{ $fleet->is_active ? 'Nonaktifkan' : 'Aktifkan' }} armada {{ $fleet->nomor_polisi }}?')) $event.preventDefault()">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-sm font-medium {{ $fleet->is_active ? 'text-rose-700 hover:text-rose-900' : 'text-emerald-700 hover:text-emerald-900' }}">{{ $fleet->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">Belum ada armada yang cocok dengan filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($fleets->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">{{ $fleets->links() }}</div>
        @endif
    </div>
@endsection
