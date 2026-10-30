@extends('layouts.app')

@section('title', 'Scanner — ' . $order->nomor_do)

@section('content')
<div class="mx-auto max-w-5xl space-y-6"
     x-data="{
        scanning: false,
        loading: false,
        lastResult: null,
        history: [],
        loadedCount: {{ $order->loadScans->where('result.value', 'valid')->count() ?? $order->items->filter(fn($i) => $i->dtyRoll?->status?->value === 'loaded')->count() }},
        totalRol: {{ $order->items->count() }},

        async submitToken(token) {
            if (this.loading || !token.trim()) return;
            this.loading = true;
            try {
                const resp = await fetch('{{ route('warehouse.scans.scan', $order) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ qr_token: token.trim() }),
                });
                const data = await resp.json();
                this.lastResult = data;
                this.loadedCount = data.loaded_count;
                this.history.unshift({
                    time: new Date().toLocaleTimeString('id-ID'),
                    roll: data.roll?.kode_rol ?? '—',
                    lot: data.roll?.nomor_lot ?? '—',
                    result: data.result,
                    result_label: data.result_label,
                    message: data.message,
                });
                this.$refs.tokenInput.value = '';
                this.$refs.tokenInput.focus();
            } catch(e) {
                this.lastResult = { success: false, message: 'Gagal terhubung ke server.', result: 'unknown' };
            } finally {
                this.loading = false;
            }
        },

        resultClass(result) {
            const map = {
                valid: 'bg-emerald-50 border-emerald-300 text-emerald-800',
                wrong_do: 'bg-rose-50 border-rose-300 text-rose-800',
                duplicate: 'bg-amber-50 border-amber-300 text-amber-800',
                unknown: 'bg-slate-50 border-slate-300 text-slate-700',
            };
            return map[result] ?? map.unknown;
        },

        badgeClass(result) {
            const map = {
                valid: 'bg-emerald-100 text-emerald-800 ring-emerald-200',
                wrong_do: 'bg-rose-100 text-rose-800 ring-rose-200',
                duplicate: 'bg-amber-100 text-amber-800 ring-amber-200',
                unknown: 'bg-slate-100 text-slate-700 ring-slate-200',
            };
            return map[result] ?? map.unknown;
        }
     }">

    {{-- Header --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Scanner Muatan</h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ $order->nomor_do }} &mdash; {{ $order->customer->nama_pabrik }} &mdash; {{ $order->fleet->nomor_polisi }}
            </p>
        </div>
        <a href="{{ route('warehouse.scans.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">Kembali ke daftar DO</a>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        {{-- Progress --}}
        <div class="sm:col-span-2 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-slate-700">Progress Muatan</p>
                <p class="text-sm font-bold tabular-nums" x-text="loadedCount + ' / ' + totalRol + ' rol'"></p>
            </div>
            <div class="mt-3 h-3 w-full overflow-hidden rounded-full bg-slate-100">
                <div class="h-3 rounded-full bg-indigo-600 transition-all duration-500"
                     x-bind:style="'width:' + (totalRol > 0 ? Math.round((loadedCount / totalRol) * 100) : 0) + '%'"></div>
            </div>
            <p class="mt-1.5 text-right text-xs text-slate-500"
               x-text="totalRol > 0 ? Math.round((loadedCount / totalRol) * 100) + '%' : '0%'"></p>

            <template x-if="loadedCount >= totalRol && totalRol > 0">
                <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                    ✅ Semua gulungan sudah berhasil dimuat ke armada!
                </div>
            </template>
        </div>

        {{-- DO Info --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm text-sm space-y-2">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Info DO</p>
            <p><span class="text-slate-500">Status:</span>
                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $order->status->badgeClass() }}">{{ $order->status->label() }}</span>
            </p>
            <p><span class="text-slate-500">Tanggal:</span> {{ $order->tanggal->format('d M Y') }}</p>
            <p><span class="text-slate-500">Driver:</span> {{ $order->driver->nama }}</p>
        </div>
    </div>

    {{-- Input Scanner --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
        <h2 class="text-base font-semibold text-slate-800">Pindai QR Code</h2>
        <p class="text-sm text-slate-500">Arahkan kamera scanner ke QR code pada label gulungan, atau ketik token QR secara manual lalu tekan Enter.</p>

        <form x-on:submit.prevent="submitToken($refs.tokenInput.value)" class="flex gap-3">
            <input x-ref="tokenInput" type="text" placeholder="Token QR akan terisi otomatis saat dipindai..." autofocus
                   class="flex-1 rounded-lg border border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 font-mono">
            <button type="submit" x-bind:disabled="loading"
                    class="rounded-lg bg-indigo-700 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-indigo-800 disabled:opacity-50">
                <span x-show="!loading">Validasi</span>
                <span x-show="loading" x-cloak>Memproses...</span>
            </button>
        </form>

        {{-- Hasil Scan Terbaru --}}
        <template x-if="lastResult">
            <div class="rounded-xl border px-5 py-4 transition-all"
                 x-bind:class="resultClass(lastResult.result)">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-base font-semibold" x-text="lastResult.message"></p>
                        <template x-if="lastResult.roll">
                            <p class="mt-1 text-sm font-mono" x-text="lastResult.roll.kode_rol + ' · Lot ' + lastResult.roll.nomor_lot + ' · ' + lastResult.roll.denier + 'D/' + lastResult.roll.filamen + 'F · ' + lastResult.roll.berat_gram + 'gr'"></p>
                        </template>
                    </div>
                    <span class="shrink-0 inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset"
                          x-bind:class="badgeClass(lastResult.result)"
                          x-text="lastResult.result_label"></span>
                </div>
            </div>
        </template>
    </div>

    {{-- Riwayat Scan Sesi Ini --}}
    <template x-if="history.length > 0">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-4 flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-800">Riwayat Sesi Ini</h2>
                <span class="text-sm text-slate-500" x-text="history.length + ' scan'"></span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">Waktu</th>
                            <th class="px-5 py-3 font-medium">Kode Rol</th>
                            <th class="px-5 py-3 font-medium">Lot</th>
                            <th class="px-5 py-3 font-medium">Hasil</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(scan, idx) in history" x-bind:key="idx">
                            <tr>
                                <td class="px-5 py-3 tabular-nums text-xs text-slate-500" x-text="scan.time"></td>
                                <td class="px-5 py-3 font-mono text-xs font-semibold text-slate-800" x-text="scan.roll"></td>
                                <td class="px-5 py-3 text-slate-600" x-text="scan.lot"></td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
                                          x-bind:class="badgeClass(scan.result)"
                                          x-text="scan.result_label"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </template>

    {{-- Daftar Rol DO --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4">
            <h2 class="text-base font-semibold text-slate-800">Checklist Gulungan DO</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3 font-medium">Kode Rol</th>
                        <th class="px-5 py-3 font-medium">Nomor Lot</th>
                        <th class="px-5 py-3 font-medium">Spesifikasi</th>
                        <th class="px-5 py-3 font-medium text-right">Berat (gram)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($order->items as $item)
                        <tr class="{{ $item->dtyRoll->status->value === 'loaded' ? 'bg-emerald-50' : '' }}">
                            <td class="px-5 py-3">
                                @if ($item->dtyRoll->status->value === 'loaded')
                                    <span class="text-emerald-700 font-bold">✓</span>
                                @else
                                    <span class="text-slate-300">○</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 font-mono text-xs font-semibold text-slate-800">{{ $item->dtyRoll->kode_rol }}</td>
                            <td class="px-5 py-3 text-slate-700">{{ $item->dtyRoll->nomor_lot }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $item->dtyRoll->denier }}D / {{ $item->dtyRoll->filamen }}F</td>
                            <td class="px-5 py-3 text-right tabular-nums">{{ number_format($item->dtyRoll->berat_gram, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
