<div class="grid gap-5 sm:grid-cols-2">
    <x-field label="Nomor polisi" name="nomor_polisi" required>
        <x-input id="nomor_polisi" name="nomor_polisi" type="text" maxlength="20" value="{{ old('nomor_polisi', $fleet->nomor_polisi ?? '') }}" class="uppercase" required />
    </x-field>

    <x-field label="Jenis kendaraan" name="jenis_kendaraan" required>
        <x-input id="jenis_kendaraan" name="jenis_kendaraan" type="text" maxlength="50" value="{{ old('jenis_kendaraan', $fleet->jenis_kendaraan ?? '') }}" required />
    </x-field>

    <x-field label="Kapasitas (kg)" name="kapasitas_kg" required>
        <x-input id="kapasitas_kg" name="kapasitas_kg" type="number" min="0.01" step="0.01" value="{{ old('kapasitas_kg', $fleet->kapasitas_kg ?? '') }}" required />
    </x-field>

    <x-field label="Pengemudi utama (opsional)" name="driver_id" :hint="$drivers->isEmpty() ? 'Belum ada akun driver aktif yang dapat dipilih.' : null">
        <x-select id="driver_id" name="driver_id">
            <option value="">Belum ditentukan</option>
            @foreach ($drivers as $driver)
                <option value="{{ $driver->id }}" @selected((string) old('driver_id', $fleet->driver_id ?? '') === (string) $driver->id)>{{ $driver->nama }}</option>
            @endforeach
        </x-select>
    </x-field>
</div>
