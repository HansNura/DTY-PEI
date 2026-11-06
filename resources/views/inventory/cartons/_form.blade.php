@php
    $isEdit = isset($carton);
    $grossWeight = old('gross_weight_kg', $isEdit ? $carton->gross_weight_kg : '');
    $netWeight = old('net_weight_kg', $isEdit ? $carton->net_weight_kg : '');
    $unitBobbins = old('unit_bobbins', $isEdit ? $carton->unit_bobbins : 6);
@endphp

<div class="space-y-6"
     x-data="{
        grossWeight: '{{ $grossWeight }}',
        netWeight: '{{ $netWeight }}',
        unitBobbins: '{{ $unitBobbins }}',
        get perBobbinValue() {
            const net = parseFloat(this.netWeight);
            const unit = parseInt(this.unitBobbins);
            return (! isNaN(net) && ! isNaN(unit) && unit > 0) ? (net * 1000 / unit) : null;
        },
        get perBobbin() {
            return this.perBobbinValue !== null ? this.perBobbinValue.toFixed(2) : '0.00';
        },
        get weightGrade() {
            const gram = this.perBobbinValue;
            if (gram === null) return null;
            if (gram >= 5350 && gram <= 5600) return 'AX';
            if (gram >= 5100 && gram < 5350) return 'AE';
            if (gram >= 1000 && gram < 5100) return 'A';
            if (gram >= 500 && gram < 1000) return 'B';
            return null;
        },
        get grossTolerance() {
            if (this.weightGrade !== 'AX' || parseInt(this.unitBobbins) !== 6) return null;
            return { min: 34.74, max: 36.25, center: 35.64 };
        },
        get grossValue() {
            const value = parseFloat(this.grossWeight);
            return Number.isFinite(value) ? value : null;
        },
        get grossWithinTolerance() {
            const range = this.grossTolerance;
            if (! range || this.grossValue === null) return null;
            return this.grossValue >= range.min && this.grossValue <= range.max;
        }
     }">
    <x-card title="Identitas kardus" description="Nomor HU dan lot/merge sesuai label fisik">
        <div class="grid gap-5 sm:grid-cols-2">
            <x-field label="Nomor HU / Carton" name="nomor_hu" required hint="Contoh: ADMG00054918">
                <x-input id="nomor_hu" name="nomor_hu" type="text" maxlength="50" value="{{ old('nomor_hu', $isEdit ? $carton->nomor_hu : '') }}" class="font-mono uppercase" required />
            </x-field>

            <x-field label="Lot / Merge No" name="nomor_merge" required hint="Contoh: 4D9164">
                <x-input id="nomor_merge" name="nomor_merge" type="text" maxlength="50" value="{{ old('nomor_merge', $isEdit ? $carton->nomor_merge : '') }}" class="font-mono uppercase" required />
            </x-field>

            <x-field label="Grade" name="grade" required hint="Contoh: WE. Grade bobot AX/AE/A/B dihitung otomatis dari rata-rata bobbin.">
                <x-input id="grade" name="grade" type="text" maxlength="10" value="{{ old('grade', $isEdit ? $carton->grade : 'WE') }}" class="uppercase" required />
            </x-field>

            <x-field label="Tanggal produksi" name="production_date" required>
                <x-input id="production_date" name="production_date" type="date" value="{{ old('production_date', $isEdit ? $carton->production_date->toDateString() : now()->toDateString()) }}" required />
            </x-field>

            <x-field label="Nomor pallet" name="pallet_number" hint="Contoh: ADP063296">
                <x-input id="pallet_number" name="pallet_number" type="text" maxlength="50" value="{{ old('pallet_number', $isEdit ? $carton->pallet_number : '') }}" class="font-mono uppercase" />
            </x-field>
        </div>
    </x-card>

    <x-card title="Spesifikasi benang" description="Contoh label: DTY 150/48 NI Z">
        <div class="grid gap-5 sm:grid-cols-2">
            <x-field label="Denier" name="denier" required>
                <x-input id="denier" name="denier" type="number" min="1" step="1" value="{{ old('denier', $isEdit ? $carton->denier : 150) }}" required />
            </x-field>

            <x-field label="Jumlah filamen" name="filamen" required>
                <x-input id="filamen" name="filamen" type="number" min="1" step="1" value="{{ old('filamen', $isEdit ? $carton->filamen : 48) }}" required />
            </x-field>

            <div class="sm:col-span-2">
                <input type="hidden" name="intermingled" value="0">
                <x-checkbox name="intermingled" value="1" :checked="(bool) old('intermingled', $isEdit ? $carton->intermingled : false)"
                            label="Intermingled (I)" description="Biarkan kosong untuk NI (Non-Intermingled), seperti pada label DTY 150/48 NI Z." />
            </div>

            <x-field label="Arah twist" name="twist_direction" hint="Z atau S. Kosongkan bila tidak tercantum.">
                <x-select id="twist_direction" name="twist_direction">
                    <option value="">Tidak dicantumkan</option>
                    <option value="Z" @selected(old('twist_direction', $isEdit ? $carton->twist_direction : 'Z') === 'Z')>Z</option>
                    <option value="S" @selected(old('twist_direction', $isEdit ? $carton->twist_direction : '') === 'S')>S</option>
                </x-select>
            </x-field>
        </div>
    </x-card>

    <x-card title="Berat dan isi kardus" description="Gross weight termasuk kardus dan paper tube; net weight hanya benang">
        <div class="grid gap-5 sm:grid-cols-3">
            <x-field label="Gross weight (kg)" name="gross_weight_kg" required>
                <x-input id="gross_weight_kg" name="gross_weight_kg" type="number" min="0.01" step="0.01" x-model="grossWeight" value="{{ $grossWeight }}" placeholder="Contoh: 35.56" required />
            </x-field>

            <x-field label="Net weight (kg)" name="net_weight_kg" required>
                <x-input id="net_weight_kg" name="net_weight_kg" type="number" min="0.01" step="0.01" x-model="netWeight" value="{{ $netWeight }}" placeholder="Contoh: 32.91" required />
            </x-field>

            <x-field label="Unit bobbin (qty)" name="unit_bobbins" required :hint="$isEdit ? 'Tidak dapat diubah setelah kardus dibuat.' : null">
                <x-input id="unit_bobbins" name="unit_bobbins" type="number" min="1" max="100" step="1" x-model="unitBobbins" value="{{ $unitBobbins }}" :readonly="$isEdit" required />
            </x-field>
        </div>

        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <div class="rounded-xl bg-brand-50 px-4 py-3 text-sm text-brand-800">
                <p>Estimasi berat per bobbin: <span class="font-semibold tabular-nums" x-text="perBobbin + ' gram'"></span></p>
                <p class="mt-1 text-xs text-brand-700">Grade bobot otomatis: <span class="font-semibold" x-text="weightGrade ?? 'Di luar standar'"></span></p>
            </div>

            <div x-cloak x-show="grossTolerance" class="rounded-xl bg-amber-50 px-4 py-3 text-amber-800">
                <p class="text-xs font-semibold uppercase tracking-wide">Toleransi gross Grade AX · 6 bobbin</p>
                <p class="mt-1 text-sm">34,74–36,25 kg · target tengah 35,64 kg</p>
                <p x-show="grossWithinTolerance !== null" class="mt-1 text-xs font-medium" :class="grossWithinTolerance ? 'text-emerald-700' : 'text-rose-700'">
                    <span x-text="grossWithinTolerance ? 'Gross saat ini dalam toleransi.' : 'Gross saat ini di luar toleransi.'"></span>
                </p>
            </div>
        </div>

        <p class="mt-2 text-xs text-slate-500">Acuan grade bobot: AX 5.350–5.600 g · AE 5.100–5.349 g · A 1.000–5.099 g · B 500–999 g.</p>
    </x-card>

    <x-card title="Catatan" description="Opsional, untuk informasi tambahan operator">
        <x-textarea id="catatan" name="catatan" rows="2" maxlength="500">{{ old('catatan', $isEdit ? $carton->catatan : '') }}</x-textarea>
    </x-card>
</div>
