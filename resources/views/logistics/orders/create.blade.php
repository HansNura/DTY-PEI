@extends('layouts.app')

@section('title', 'Terbitkan Surat Jalan')

@section('content')
    <div class="mx-auto max-w-4xl">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Terbitkan Surat Jalan</h1>
                <p class="mt-1 text-sm text-slate-500">Pilih pelanggan, armada, driver, dan alokasikan gulungan DTY yang akan dikirim.</p>
            </div>
            <a href="{{ route('logistics.orders.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">Kembali</a>
        </div>

        <form method="POST" action="{{ route('logistics.orders.store') }}" class="mt-6 space-y-6" x-data="{
            selectedRolls: [],
            toggleRoll(id) {
                const idx = this.selectedRolls.indexOf(id);
                if (idx === -1) this.selectedRolls.push(id);
                else this.selectedRolls.splice(idx, 1);
            },
            isSelected(id) { return this.selectedRolls.includes(id); }
        }">
            @csrf

            {{-- Info Pengiriman --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-semibold text-slate-800">Informasi Pengiriman</h2>
                <div class="mt-4 grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="tanggal" class="block text-sm font-medium text-slate-700">Tanggal pengiriman</label>
                        <input id="tanggal" name="tanggal" type="date" value="{{ old('tanggal', now()->toDateString()) }}" required
                               class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                    </div>
                    <div>
                        <label for="customer_id" class="block text-sm font-medium text-slate-700">Pabrik pelanggan tujuan</label>
                        <select id="customer_id" name="customer_id" required
                                class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                            <option value="">— Pilih pelanggan —</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>
                                    {{ $customer->nama_pabrik }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="fleet_id" class="block text-sm font-medium text-slate-700">Armada kendaraan</label>
                        <select id="fleet_id" name="fleet_id" required
                                class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                            <option value="">— Pilih armada —</option>
                            @foreach ($fleets as $fleet)
                                <option value="{{ $fleet->id }}" @selected(old('fleet_id') == $fleet->id)>
                                    {{ $fleet->nomor_polisi }} — {{ $fleet->jenis_kendaraan }} ({{ number_format($fleet->kapasitas_kg, 0) }} kg)
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="driver_id" class="block text-sm font-medium text-slate-700">Driver / pengemudi</label>
                        <select id="driver_id" name="driver_id" required
                                class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                            <option value="">— Pilih driver —</option>
                            @foreach ($drivers as $driver)
                                <option value="{{ $driver->id }}" @selected(old('driver_id') == $driver->id)>{{ $driver->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="catatan" class="block text-sm font-medium text-slate-700">Catatan (opsional)</label>
                        <textarea id="catatan" name="catatan" rows="2" maxlength="1000"
                                  class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">{{ old('catatan') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Pilih Gulungan DTY --}}
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-6 py-4">
                    <div>
                        <h2 class="text-base font-semibold text-slate-800">Alokasi Gulungan DTY</h2>
                        <p class="text-sm text-slate-500">Centang gulungan yang akan dimuat ke armada ini. Hanya gulungan berstatus <em>Tersedia</em> yang ditampilkan.</p>
                    </div>
                    <span class="text-sm font-semibold text-indigo-700" x-text="selectedRolls.length + ' dipilih'"></span>
                </div>

                @if ($availableRolls->isEmpty())
                    <p class="px-6 py-8 text-center text-sm text-slate-500">Tidak ada gulungan DTY yang tersedia di stok saat ini.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-5 py-3 font-medium">
                                        <input type="checkbox" class="rounded border-slate-300"
                                               x-on:change="$el.checked
                                                    ? selectedRolls = {{ $availableRolls->pluck('id')->toJson() }}
                                                    : selectedRolls = []">
                                    </th>
                                    <th class="px-5 py-3 font-medium">Kode Rol</th>
                                    <th class="px-5 py-3 font-medium">Nomor Lot</th>
                                    <th class="px-5 py-3 font-medium">Spesifikasi</th>
                                    <th class="px-5 py-3 font-medium text-right">Berat (gram)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($availableRolls as $roll)
                                    <tr class="cursor-pointer hover:bg-indigo-50"
                                        x-bind:class="isSelected({{ $roll->id }}) ? 'bg-indigo-50' : ''"
                                        x-on:click="toggleRoll({{ $roll->id }})">
                                        <td class="px-5 py-3">
                                            <input type="checkbox" name="rolls[]" value="{{ $roll->id }}"
                                                   class="rounded border-slate-300 text-indigo-600"
                                                   x-bind:checked="isSelected({{ $roll->id }})"
                                                   x-on:click.stop="toggleRoll({{ $roll->id }})">
                                        </td>
                                        <td class="px-5 py-3 font-mono text-xs font-semibold text-slate-800">{{ $roll->kode_rol }}</td>
                                        <td class="px-5 py-3 text-slate-700">{{ $roll->nomor_lot }}</td>
                                        <td class="px-5 py-3 text-slate-600">{{ $roll->denier }}D / {{ $roll->filamen }}F</td>
                                        <td class="px-5 py-3 text-right tabular-nums text-slate-700">{{ number_format($roll->berat_gram, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('logistics.orders.index') }}" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900">Batal</a>
                <button type="submit" class="rounded-lg bg-indigo-700 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-indigo-800">
                    Terbitkan Surat Jalan
                </button>
            </div>
        </form>
    </div>
@endsection
