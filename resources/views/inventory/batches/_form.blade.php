@php
    $isEdit = isset($batch);
@endphp

<div class="grid gap-5 sm:grid-cols-2">
    <x-field label="Merge / Lot No" name="nomor_merge" required hint="Nomor merge produksi, contoh 4D9164.">
        <x-input id="nomor_merge" name="nomor_merge" type="text" maxlength="50" value="{{ old('nomor_merge', $isEdit ? $batch->nomor_merge : '') }}" placeholder="Contoh: 4D9164" class="font-mono uppercase" required />
    </x-field>

    <x-field label="Tanggal produksi" name="production_date" required hint="Tanggal doffing/produksi; dipakai untuk serial bobbin.">
        <x-input id="production_date" name="production_date" type="date" value="{{ old('production_date', $isEdit ? $batch->production_date->toDateString() : now()->toDateString()) }}" required />
    </x-field>

    <x-field label="Denier (D)" name="denier" required>
        <x-input id="denier" name="denier" type="number" min="1" step="1" value="{{ old('denier', $isEdit ? $batch->denier : 150) }}" placeholder="Contoh: 150" required />
    </x-field>

    <x-field label="Jumlah filamen (F)" name="filamen" required>
        <x-input id="filamen" name="filamen" type="number" min="1" step="1" value="{{ old('filamen', $isEdit ? $batch->filamen : 48) }}" placeholder="Contoh: 48" required />
    </x-field>

    <div class="sm:col-span-2">
        <input type="hidden" name="intermingled" value="0">
        <x-checkbox name="intermingled" value="1" :checked="(bool) old('intermingled', $isEdit ? $batch->intermingled : false)"
                    label="Intermingled (I)" description="Biarkan kosong untuk NI (Non-Intermingled)." />
    </div>

    <x-field label="Arah twist" name="twist_direction" hint="Z atau S. Kosongkan bila tidak tercantum.">
        <x-select id="twist_direction" name="twist_direction">
            <option value="">Tidak dicantumkan</option>
            <option value="Z" @selected(old('twist_direction', $isEdit ? $batch->twist_direction : 'Z') === 'Z')>Z</option>
            <option value="S" @selected(old('twist_direction', $isEdit ? $batch->twist_direction : '') === 'S')>S</option>
        </x-select>
    </x-field>
</div>
