@extends('layouts.app')

@section('title', 'Pelanggan')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Master pelanggan</h1>
            <p class="mt-1 text-sm text-slate-500">Kelola pabrik tujuan, alamat, koordinat, dan kontak penerima.</p>
        </div>
        <a href="{{ route('logistics.customers.create') }}" class="rounded-lg bg-indigo-700 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-indigo-800">Tambah pelanggan</a>
    </div>

    <form method="GET" action="{{ route('logistics.customers.index') }}" class="mt-6 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-[1.6fr_1fr_auto]">
        <label for="q" class="sr-only">Cari pelanggan atau kontak</label>
        <input id="q" name="q" type="search" value="{{ $search }}" placeholder="Cari pelanggan atau kontak"
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
        <label for="status" class="sr-only">Status</label>
        <select id="status" name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
            <option value="">Semua status</option>
            <option value="active" @selected($status === 'active')>Aktif</option>
            <option value="inactive" @selected($status === 'inactive')>Nonaktif</option>
        </select>
        <div class="flex gap-2">
            <button type="submit" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Filter</button>
            <a href="{{ route('logistics.customers.index') }}" class="rounded-lg px-3 py-2 text-sm text-slate-500 hover:text-slate-800">Reset</a>
        </div>
    </form>

    <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Pabrik / Alamat</th>
                        <th class="px-5 py-3 font-medium">Kontak penerima</th>
                        <th class="px-5 py-3 font-medium">Titik maps</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($customers as $customer)
                        <tr>
                            <td class="px-5 py-3">
                                <p class="font-medium text-slate-900">{{ $customer->nama_pabrik }}</p>
                                <p class="max-w-sm text-xs text-slate-500">{{ $customer->alamat }}</p>
                            </td>
                            <td class="px-5 py-3">
                                <p class="font-medium text-slate-800">{{ $customer->kontak_nama }}</p>
                                <p class="text-xs text-slate-500">{{ $customer->kontak_telepon }}</p>
                            </td>
                            <td class="px-5 py-3">
                                @if ($customer->latitude !== null && $customer->longitude !== null)
                                    <a href="https://maps.google.com/?q={{ $customer->latitude }},{{ $customer->longitude }}" target="_blank" rel="noopener noreferrer" class="text-indigo-700 hover:text-indigo-900">{{ $customer->latitude }}, {{ $customer->longitude }}</a>
                                @else
                                    <span class="text-slate-400">Belum diatur</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $customer->is_active ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-rose-50 text-rose-700 ring-rose-200' }}">{{ $customer->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('logistics.customers.edit', $customer) }}" class="text-sm font-medium text-indigo-700 hover:text-indigo-900">Edit</a>
                                    <form method="POST" action="{{ route('logistics.customers.toggle-active', $customer) }}" x-data x-on:submit="if (! confirm('{{ $customer->is_active ? 'Nonaktifkan' : 'Aktifkan' }} pelanggan {{ $customer->nama_pabrik }}?')) $event.preventDefault()">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-sm font-medium {{ $customer->is_active ? 'text-rose-700 hover:text-rose-900' : 'text-emerald-700 hover:text-emerald-900' }}">{{ $customer->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-slate-500">Belum ada pelanggan yang cocok dengan filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($customers->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">{{ $customers->links() }}</div>
        @endif
    </div>
@endsection
