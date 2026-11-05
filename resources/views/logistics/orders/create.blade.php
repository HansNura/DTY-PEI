@extends('layouts.app')

@section('title', 'Terbitkan Surat Jalan')

@section('content')
    @php
        $rollWeights = $availableRolls->mapWithKeys(fn ($roll) => [$roll->id => (float) $roll->berat_gram]);
        $fleetCapacities = $fleets->mapWithKeys(fn ($fleet) => [$fleet->id => (float) $fleet->kapasitas_kg]);
    @endphp

    <div class="mx-auto max-w-6xl space-y-6"
         x-data="{
            selectedRolls: @js(array_map('intval', old('rolls', []))),
            fleetId: @js((string) old('fleet_id', '')),
            rollWeights: @js($rollWeights),
            fleetCapacities: @js($fleetCapacities),
            toggleRoll(id) {
                const idx = this.selectedRolls.indexOf(id);
                if (idx === -1) this.selectedRolls.push(id);
                else this.selectedRolls.splice(idx, 1);
            },
            isSelected(id) { return this.selectedRolls.includes(id); },
            get selectedWeightKg() {
                return this.selectedRolls.reduce((sum, id) => sum + (this.rollWeights[id] ?? 0), 0) / 1000;
            },
            get selectedCapacityKg() {
                return this.fleetCapacities[this.fleetId] ?? null;
            },
            get overCapacity() {
                return this.selectedCapacityKg !== null && this.selectedWeightKg > this.selectedCapacityKg;
            }
         }">
        <x-page-header title="Terbitkan Surat Jalan" description="Pilih pelanggan, armada, driver, dan alokasikan gulungan DTY yang akan dikirim.">
            <x-slot:actions>
                <x-button href="{{ route('logistics.orders.index') }}" variant="ghost" icon="arrow-left">Kembali</x-button>
            </x-slot:actions>
        </x-page-header>

        <form method="POST" action="{{ route('logistics.orders.store') }}" class="grid gap-6 lg:grid-cols-[1fr_320px]">
            @csrf

            <div class="space-y-6">
                <x-card title="Informasi pengiriman">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-field label="Tanggal pengiriman" name="tanggal" required>
                            <x-input id="tanggal" name="tanggal" type="date" value="{{ old('tanggal', now()->toDateString()) }}" required />
                        </x-field>

                        <x-field label="Pabrik pelanggan tujuan" name="customer_id" required>
                            <x-select id="customer_id" name="customer_id" required>
                                <option value="">Pilih pelanggan</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>{{ $customer->nama_pabrik }}</option>
                                @endforeach
                            </x-select>
                        </x-field>

                        <x-field label="Armada kendaraan" name="fleet_id" required>
                            <x-select id="fleet_id" name="fleet_id" x-model="fleetId" required>
                                <option value="">Pilih armada</option>
                                @foreach ($fleets as $fleet)
                                    <option value="{{ $fleet->id }}">{{ $fleet->nomor_polisi }} — {{ $fleet->jenis_kendaraan }} ({{ number_format((float) $fleet->kapasitas_kg, 0, ',', '.') }} kg)</option>
                                @endforeach
                            </x-select>
                        </x-field>

                        <x-field label="Driver / pengemudi" name="driver_id" required>
                            <x-select id="driver_id" name="driver_id" required>
                                <option value="">Pilih driver</option>
                                @foreach ($drivers as $driver)
                                    <option value="{{ $driver->id }}" @selected(old('driver_id') == $driver->id)>{{ $driver->nama }}</option>
                                @endforeach
                            </x-select>
                        </x-field>

                        <x-field label="Catatan (opsional)" name="catatan" class="sm:col-span-2">
                            <x-textarea id="catatan" name="catatan" rows="2" maxlength="1000">{{ old('catatan') }}</x-textarea>
                        </x-field>
                    </div>
                </x-card>

                <x-card padding="p-0" title="Alokasi gulungan DTY" description="Centang gulungan yang akan dimuat. Hanya stok tersedia yang ditampilkan.">
                    <x-slot:actions>
                        <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700" x-text="selectedRolls.length + ' dipilih'"></span>
                    </x-slot:actions>

                    @if ($availableRolls->isEmpty())
                        <div class="p-6">
                            <x-empty-state icon="package" title="Tidak ada gulungan tersedia" description="Semua stok sedang dialokasikan atau belum dicatat." />
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-100 text-sm">
                                <thead class="bg-slate-50/70">
                                    <tr>
                                        <x-th>
                                            <input type="checkbox" class="size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                                                   x-on:change="$el.checked ? selectedRolls = @js($availableRolls->pluck('id')->map(fn ($id) => (int) $id)) : selectedRolls = []"
                                                   aria-label="Pilih semua gulungan">
                                        </x-th>
                                        <x-th>Kode rol</x-th>
                                        <x-th>Kardus</x-th>
                                        <x-th>Nomor lot</x-th>
                                        <x-th>Spesifikasi</x-th>
                                        <x-th align="right">Berat (g)</x-th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($availableRolls as $roll)
                                        <tr class="cursor-pointer transition hover:bg-brand-50/40"
                                            x-bind:class="isSelected({{ $roll->id }}) ? 'bg-brand-50/60' : ''"
                                            x-on:click="toggleRoll({{ $roll->id }})">
                                            <x-td>
                                                <input type="checkbox" name="rolls[]" value="{{ $roll->id }}"
                                                       class="size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                                                       x-bind:checked="isSelected({{ $roll->id }})"
                                                       x-on:click.stop="toggleRoll({{ $roll->id }})">
                                            </x-td>
                                            <x-td><span class="font-mono text-xs font-semibold text-slate-800">{{ $roll->kode_rol }}</span></x-td>
                                            <x-td>
                                                <span class="font-mono text-xs text-slate-700">{{ $roll->carton?->nomor_hu ?? '—' }}</span>
                                                @if ($roll->carton)
                                                    <p class="text-[11px] text-slate-500">Merge {{ $roll->carton->nomor_merge }}</p>
                                                @endif
                                            </x-td>
                                            <x-td>{{ $roll->nomor_lot }}</x-td>
                                            <x-td class="tabular-nums">{{ $roll->denier }}D / {{ $roll->filamen }}F</x-td>
                                            <x-td align="right" class="tabular-nums">{{ number_format((float) $roll->berat_gram, 2, ',', '.') }}</x-td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </x-card>
            </div>

            <div class="lg:sticky lg:top-24 lg:self-start">
                <x-card title="Ringkasan DO">
                    <dl class="space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-500">Gulungan dipilih</dt>
                            <dd class="font-semibold tabular-nums text-slate-900" x-text="selectedRolls.length"></dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-500">Total berat</dt>
                            <dd class="font-semibold tabular-nums text-slate-900" x-text="selectedWeightKg.toFixed(2) + ' kg'"></dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-500">Kapasitas armada</dt>
                            <dd class="font-semibold tabular-nums text-slate-900" x-text="selectedCapacityKg !== null ? Number(selectedCapacityKg).toFixed(0) + ' kg' : '—'"></dd>
                        </div>
                    </dl>

                    <template x-if="overCapacity">
                        <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-medium text-rose-700">
                            Total berat melebihi kapasitas armada. Kurangi pilihan gulungan.
                        </div>
                    </template>

                    <div class="mt-5 space-y-2">
                        <x-button type="submit" class="w-full" icon="check" x-bind:disabled="selectedRolls.length === 0 || overCapacity">
                            Terbitkan Surat Jalan
                        </x-button>
                        <x-button href="{{ route('logistics.orders.index') }}" variant="secondary" class="w-full">Batal</x-button>
                    </div>
                </x-card>
            </div>
        </form>
    </div>
@endsection
