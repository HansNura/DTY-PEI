@extends('layouts.app')

@section('title', 'Terbitkan Surat Jalan')

@section('content')
    @php
        $fleetCapacities = $fleets->mapWithKeys(fn ($fleet) => [$fleet->id => (float) $fleet->kapasitas_kg]);
    @endphp

    <div class="mx-auto max-w-6xl space-y-6"
         x-data="{
            selectedRolls: @js(array_map('intval', old('rolls', []))),
            fleetId: @js((string) old('fleet_id', '')),
            driverId: @js((string) old('driver_id', '')),
            fleetDrivers: @js($fleetDrivers),
            allRollIds: @js($allRollIds),
            get fleetDriverId() {
                return this.fleetDrivers[this.fleetId] ?? null;
            },
            syncDriverFromFleet() {
                const driver = this.fleetDriverId;

                if (driver) this.driverId = String(driver);
            },
            rollWeightsKg: @js($rollWeightsKg),
            rollCartonKeys: @js($rollCartonKeys),
            cartonMeta: @js($cartonMeta),
            fleetCapacities: @js($fleetCapacities),
            isSelected(id) {
                return this.selectedRolls.includes(id);
            },
            toggleMany(ids) {
                const allSelected = ids.length > 0 && ids.every((id) => this.selectedRolls.includes(id));

                if (allSelected) {
                    this.selectedRolls = this.selectedRolls.filter((id) => ! ids.includes(id));
                } else {
                    this.selectedRolls = Array.from(new Set([...this.selectedRolls, ...ids]));
                }
            },
            isManySelected(ids) {
                return ids.length > 0 && ids.every((id) => this.selectedRolls.includes(id));
            },
            isManyPartial(ids) {
                const selected = ids.filter((id) => this.selectedRolls.includes(id)).length;
                return selected > 0 && selected < ids.length;
            },
            get allSelected() {
                return this.allRollIds.length > 0 && this.allRollIds.every((id) => this.selectedRolls.includes(id));
            },
            get allPartial() {
                const selected = this.allRollIds.filter((id) => this.selectedRolls.includes(id)).length;
                return selected > 0 && selected < this.allRollIds.length;
            },
            toggleAll() {
                this.selectedRolls = this.allSelected ? [] : [...this.allRollIds];
            },
            get selectedWeightKg() {
                return this.selectedRolls.reduce((sum, id) => sum + (this.rollWeightsKg[id] ?? 0), 0);
            },
            get selectedCartonCount() {
                return this.selectedCartonGroups.length;
            },
            get selectedCartonGroups() {
                const keys = Array.from(new Set(this.selectedRolls
                    .map((id) => this.rollCartonKeys[id])
                    .filter((key) => key && key.startsWith('carton-'))));

                return keys
                    .map((key) => this.cartonMeta[key])
                    .filter(Boolean)
                    .sort((a, b) => a.hu.localeCompare(b.hu));
            },
            get palletPreview() {
                const groups = [];

                for (let i = 0; i < this.selectedCartonGroups.length; i += 30) {
                    groups.push(this.selectedCartonGroups.slice(i, i + 30));
                }

                return groups;
            },
            get palletCount() {
                return this.palletPreview.length;
            },
            get selectedCapacityKg() {
                return this.fleetCapacities[this.fleetId] ?? null;
            },
            get capacityUsage() {
                if (this.selectedCapacityKg === null || this.selectedCapacityKg <= 0) return 0;
                return Math.min(100, (this.selectedWeightKg / this.selectedCapacityKg) * 100);
            },
            get overCapacity() {
                return this.selectedCapacityKg !== null && this.selectedWeightKg > this.selectedCapacityKg;
            }
         }"
         x-init="syncDriverFromFleet()">
        <x-page-header title="Terbitkan Surat Jalan" description="Pilih pelanggan, armada, dan driver. Alokasikan pengiriman per bobbin, kardus, atau pallet dengan total netto dalam kg.">
            <x-slot:actions>
                <x-button href="{{ route('logistics.orders.index') }}" variant="ghost" icon="arrow-left">Kembali</x-button>
            </x-slot:actions>
        </x-page-header>

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('logistics.orders.store') }}" class="grid gap-6 lg:grid-cols-[1fr_340px]">
            @csrf

            <div class="space-y-6">
                <x-card title="Informasi pengiriman" description="Data tujuan dan armada yang mengangkut.">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-field label="Tanggal pengiriman" name="tanggal" required>
                            <x-input id="tanggal" name="tanggal" type="date" value="{{ old('tanggal', now()->toDateString()) }}" required />
                        </x-field>

                        <x-field label="Pabrik pelanggan tujuan" name="customer_id" required>
                            <x-select id="customer_id" name="customer_id" required>
                                <option value="">Pilih pelanggan</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>
                                        {{ $customer->kode_pelanggan ? $customer->kode_pelanggan.' · ' : '' }}{{ $customer->nama_pabrik }}
                                    </option>
                                @endforeach
                            </x-select>
                        </x-field>

                        <x-field label="Armada kendaraan" name="fleet_id" required>
                            <x-select id="fleet_id" name="fleet_id" x-model="fleetId" x-on:change="syncDriverFromFleet()" required>
                                <option value="">Pilih armada</option>
                                @foreach ($fleets as $fleet)
                                    <option value="{{ $fleet->id }}">{{ $fleet->nomor_polisi }} — {{ $fleet->jenis_kendaraan }} ({{ number_format((float) $fleet->kapasitas_kg, 0, ',', '.') }} kg)</option>
                                @endforeach
                            </x-select>
                        </x-field>

                        <x-field label="Driver / pengemudi" name="driver_id" required>
                            <input type="hidden" name="driver_id" x-bind:value="driverId">
                            <x-select id="driver_id" x-model="driverId" x-bind:disabled="fleetDriverId !== null" x-bind:required="fleetDriverId === null">
                                <option value="">Pilih driver</option>
                                @foreach ($drivers as $driver)
                                    <option value="{{ $driver->id }}">{{ $driver->nama }}</option>
                                @endforeach
                            </x-select>
                            <p x-show="fleetDriverId" class="mt-1 text-[10px] text-slate-500">Driver mengikuti pengemudi utama armada.</p>
                            <p x-show="! fleetDriverId" class="mt-1 text-[10px] text-slate-500">Armada belum punya driver utama; pilih driver secara manual.</p>
                        </x-field>

                        <x-field label="Catatan (opsional)" name="catatan" class="sm:col-span-2">
                            <x-textarea id="catatan" name="catatan" rows="2" maxlength="1000">{{ old('catatan') }}</x-textarea>
                        </x-field>
                    </div>
                </x-card>

                <x-card padding="p-0" title="Alokasi pengiriman" description="Centang kardus utuh atau pallet. Bobbin hanya ditampilkan sebagai isi kardus; pengiriman reguler tidak dihitung per bobbin.">
                    <x-slot:actions>
                        <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700">
                            <span x-text="selectedRolls.length"></span> bobbin dipilih
                        </span>
                    </x-slot:actions>

                    <div class="grid gap-3 border-b border-slate-100 bg-slate-50/60 px-4 py-3 sm:grid-cols-3">
                        <div class="flex items-center gap-2.5">
                            <span class="grid size-7 place-items-center rounded-lg bg-white text-[11px] font-bold text-brand-700 ring-1 ring-inset ring-brand-100">1</span>
                            <div><p class="text-xs font-semibold text-slate-700">Bobbin</p><p class="text-[10px] text-slate-500">Isi kardus, bukan unit jual</p></div>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <span class="grid size-7 place-items-center rounded-lg bg-white text-[11px] font-bold text-brand-700 ring-1 ring-inset ring-brand-100">2</span>
                            <div><p class="text-xs font-semibold text-slate-700">Kardus / Box</p><p class="text-[10px] text-slate-500">Berisi 6 bobbin</p></div>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <span class="grid size-7 place-items-center rounded-lg bg-white text-[11px] font-bold text-brand-700 ring-1 ring-inset ring-brand-100">3</span>
                            <div><p class="text-xs font-semibold text-slate-700">Pallet</p><p class="text-[10px] text-slate-500">1 pallet = 30 kardus</p></div>
                        </div>
                    </div>
                    <p class="border-b border-slate-100 bg-white px-4 py-2 text-[10px] text-slate-500">Pengiriman reguler minimal per kardus utuh. Sample cone ditangani di luar alur Surat Jalan ini.</p>

                    @if ($boxGroups->isEmpty())
                        <div class="p-6">
                            <x-empty-state icon="package" title="Tidak ada kardus siap kirim" description="Semua stok sedang dialokasikan atau belum dicatat." />
                        </div>
                    @else
                        <div class="max-h-[38rem] space-y-3 overflow-y-auto p-3 sm:p-4">
                            <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-2.5">
                                <input type="checkbox"
                                       class="size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                                       x-effect="$el.checked = allSelected; $el.indeterminate = allPartial && ! allSelected"
                                       x-on:change="toggleAll()"
                                       aria-label="Pilih semua kardus tersedia">
                                <p class="text-xs font-medium text-slate-600">Pilih semua <span class="font-normal text-slate-400">· {{ number_format($selectableRollCount, 0, ',', '.') }} bobbin dalam kardus siap kirim</span></p>
                            </div>

                            @foreach ($boxGroups as $group)
                                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                                    <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 bg-slate-50/80 px-4 py-3">
                                        <span class="grid size-8 shrink-0 place-items-center rounded-xl bg-white text-brand-700 ring-1 ring-inset ring-brand-100">
                                            <x-icon name="activity" class="size-4" />
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-semibold text-slate-800">{{ $group['label'] }}</p>
                                            <p class="mt-0.5 text-[11px] text-slate-500">{{ $group['carton_count'] }} kardus · {{ $group['bobbin_count'] }} bobbin</p>
                                            @if (! $group['selectable'])
                                                <p class="mt-0.5 text-[10px] font-medium text-amber-600">Belum ada kardus lengkap yang bisa dikirim.</p>
                                            @endif
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-semibold tabular-nums text-slate-900">{{ number_format($group['net_kg'], 2, ',', '.') }} kg</p>
                                            <p class="text-[10px] uppercase tracking-wide text-slate-400">Netto</p>
                                        </div>
                                    </div>

                                    <div class="divide-y divide-slate-100">
                                        @foreach ($group['cartons'] as $cartonGroup)
                                            @php
                                                $carton = $cartonGroup['carton'];
                                                $selectable = $cartonGroup['selectable'];
                                                $weightTone = match ($carton?->bobbin_weight_grade) {
                                                    'AX' => 'success',
                                                    'AE' => 'info',
                                                    'A' => 'warning',
                                                    'B' => 'neutral',
                                                    default => 'neutral',
                                                };
                                            @endphp

                                            <div class="border-t border-slate-100" x-data="{ ids: @js($cartonGroup['roll_ids']) }">
                                                @if ($selectable)
                                                    @foreach ($cartonGroup['rolls'] as $roll)
                                                        <input type="hidden" name="rolls[]" value="{{ $roll->id }}" x-bind:disabled="! isSelected({{ $roll->id }})">
                                                    @endforeach
                                                @endif

                                                <div class="flex flex-wrap items-center gap-3 px-4 py-3 pl-7 transition {{ $selectable ? 'cursor-pointer hover:bg-slate-50/70' : '' }}"
                                                     x-bind:class="isManySelected(ids) ? 'bg-brand-50/60' : ''"
                                                     @if ($selectable) x-on:click="toggleMany(ids)" @endif>
                                                    <input type="checkbox"
                                                           class="size-4 shrink-0 rounded border-slate-300 text-brand-600 focus:ring-brand-500 disabled:cursor-not-allowed disabled:opacity-40"
                                                           x-effect="$el.checked = isManySelected(ids); $el.indeterminate = isManyPartial(ids)"
                                                           x-on:change="toggleMany(ids)"
                                                           x-on:click.stop
                                                           @disabled(! $selectable)>
                                                    <div class="min-w-0 flex-1">
                                                        <div class="flex flex-wrap items-center gap-1.5">
                                                            <p class="font-mono text-xs font-semibold text-slate-800">{{ $carton?->nomor_hu ?? 'Tanpa kardus' }}</p>
                                                            @if ($carton)
                                                                <x-badge tone="brand">Produk {{ $carton->grade }}</x-badge>
                                                                <x-badge :tone="$weightTone">Bobot {{ $carton->bobbin_weight_grade ?? '—' }}</x-badge>
                                                            @else
                                                                <x-badge tone="neutral">Tanpa kardus</x-badge>
                                                            @endif
                                                        </div>
                                                        <p class="mt-0.5 text-[11px] text-slate-500">
                                                            Merge {{ $carton?->nomor_merge ?? $cartonGroup['rolls']->first()->nomor_lot }}
                                                            · {{ $carton?->specification ?? $cartonGroup['rolls']->first()->specification }}
                                                        </p>
                                                        @if (! $selectable)
                                                            <p class="mt-0.5 text-[10px] font-medium text-amber-600">{{ $carton ? 'Kardus belum lengkap 6 bobbin' : 'Bobbin tanpa kardus tidak dapat dikirim reguler' }}</p>
                                                        @endif
                                                    </div>
                                                    <div class="text-right">
                                                        <p class="text-xs font-semibold tabular-nums text-slate-800">{{ number_format($cartonGroup['net_kg'], 2, ',', '.') }} kg</p>
                                                        <p class="text-[10px] {{ ! $selectable ? 'font-medium text-amber-600' : 'text-slate-400' }}">{{ $cartonGroup['available_count'] }}/{{ $cartonGroup['expected_count'] }} bobbin</p>
                                                    </div>
                                                </div>

                                                <div class="divide-y divide-slate-100/80 border-t border-slate-100/70">
                                                    @foreach ($cartonGroup['rolls'] as $roll)
                                                        <div class="flex items-center gap-3 px-4 py-2.5 pl-11 text-xs"
                                                             x-bind:class="isSelected({{ $roll->id }}) ? 'bg-brand-50/50' : ''">
                                                            <span class="grid size-6 shrink-0 place-items-center rounded-lg bg-slate-100 text-slate-500">
                                                                <x-icon name="package" class="size-3.5" />
                                                            </span>
                                                            <span class="min-w-0 flex-1">
                                                                <span class="block font-mono text-[11px] font-semibold text-slate-800">{{ $roll->nomor_seri ?? '—' }}</span>
                                                                <span class="mt-0.5 block text-[10px] text-slate-500">Lot {{ $roll->nomor_lot }} · Grade {{ $roll->bobbin_weight_grade ?? '—' }}</span>
                                                            </span>
                                                            <span class="shrink-0 text-right">
                                                                <span class="block font-semibold tabular-nums text-slate-800">{{ number_format((float) $roll->berat_gram / 1000, 2, ',', '.') }} kg</span>
                                                                <span class="block text-[10px] text-slate-400">netto</span>
                                                            </span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-card>
            </div>

            <div class="lg:sticky lg:top-24 lg:self-start">
                <x-card title="Ringkasan DO">
                    <dl class="space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-500">Bobbin dipilih</dt>
                            <dd class="font-semibold tabular-nums text-slate-900" x-text="selectedRolls.length"></dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-500">Kardus dipilih</dt>
                            <dd class="font-semibold tabular-nums text-slate-900" x-text="selectedCartonCount"></dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-500">Pallet terbentuk</dt>
                            <dd class="font-semibold tabular-nums text-slate-900" x-text="palletCount"></dd>
                        </div>
                        <div class="mt-3 border-t border-slate-100 pt-3">
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-slate-500">Total netto</dt>
                                <dd class="text-lg font-semibold tabular-nums text-brand-700" x-text="selectedWeightKg.toFixed(2) + ' kg'"></dd>
                            </div>
                            <p class="mt-1 text-[10px] leading-relaxed text-slate-500">Dasar tagihan: harga per kg netto, bukan per bobbin atau kardus.</p>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-500">Kapasitas armada</dt>
                            <dd class="font-semibold tabular-nums text-slate-900" x-text="selectedCapacityKg !== null ? Number(selectedCapacityKg).toFixed(0) + ' kg' : '—'"></dd>
                        </div>
                    </dl>

                    <div x-show="selectedCapacityKg !== null" x-cloak class="mt-3">
                        <div class="h-1.5 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full transition-all" :class="overCapacity ? 'bg-rose-500' : 'bg-brand-500'" :style="'width: ' + capacityUsage + '%'"></div>
                        </div>
                        <p class="mt-1 text-right text-[10px] text-slate-400">Terpakai <span x-text="capacityUsage.toFixed(0)"></span>%</p>
                    </div>

                    <template x-if="overCapacity">
                        <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-medium text-rose-700">
                            Total netto melebihi kapasitas armada. Kurangi pilihan bobbin atau kardus.
                        </div>
                    </template>

                    <div class="mt-5 space-y-2">
                        <x-button type="submit" class="w-full" icon="check" x-bind:disabled="selectedRolls.length === 0 || overCapacity">
                            Terbitkan Surat Jalan
                        </x-button>
                        <x-button href="{{ route('logistics.orders.index') }}" variant="secondary" class="w-full">Batal</x-button>
                    </div>
                </x-card>

                <x-card title="Palletisasi otomatis" description="Pallet dibentuk per 30 kardus; pallet terakhir boleh berisi kurang dari 30." class="mt-4">
                    <div x-show="selectedCartonCount === 0" x-cloak class="rounded-xl border border-dashed border-slate-200 px-3 py-4 text-center text-xs text-slate-500">
                        Pilih kardus untuk melihat pembentukan pallet.
                    </div>

                    <div x-show="palletPreview.length > 0" x-cloak class="space-y-2">
                        <template x-for="(pallet, index) in palletPreview" :key="index">
                            <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50/60 px-3.5 py-2.5">
                                <div>
                                    <p class="text-xs font-semibold text-slate-800">Pallet <span x-text="'P' + String(index + 1).padStart(2, '0')"></span></p>
                                    <p class="mt-0.5 text-[10px] text-slate-500"><span x-text="pallet.length"></span> kardus · <span x-text="pallet.reduce((sum, box) => sum + box.net_kg, 0).toFixed(2)"></span> kg netto</p>
                                </div>
                                <span class="rounded-full bg-white px-2 py-1 text-[10px] font-semibold text-slate-500 ring-1 ring-inset ring-slate-200"><span x-text="pallet.length"></span>/30</span>
                            </div>
                        </template>
                    </div>

                    <p x-show="selectedCartonCount > 0" x-cloak class="pt-2 text-[10px] text-slate-400">Pallet terakhir boleh berisi kurang dari 30 kardus; nomor pallet final mengikuti nomor Surat Jalan.</p>
                </x-card>
            </div>
        </form>
    </div>
@endsection
