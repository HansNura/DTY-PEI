<div class="grid gap-5 sm:grid-cols-2">
    <x-field label="Kode armada" hint="Dibuat otomatis. Kode ini yang akan dipakai saat armada disimpan.">
        <div class="flex items-center justify-between gap-3 rounded-xl border border-dashed border-teal-300 bg-teal-50/70 px-3.5 py-2.5">
            <div class="flex min-w-0 items-center gap-2.5">
                <span class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-white text-teal-700 ring-1 ring-teal-200">
                    <x-icon name="lock" class="size-3.5" />
                </span>
                <span class="truncate font-mono text-sm font-semibold tracking-wide text-teal-950">{{ $fleet->kode_armada ?? $previewCode ?? '—' }}</span>
            </div>
            <span class="shrink-0 rounded-full bg-teal-700 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-white">Otomatis</span>
        </div>
    </x-field>

    <x-field label="Nomor polisi" name="nomor_polisi" required>
        <x-input id="nomor_polisi" name="nomor_polisi" type="text" maxlength="20" value="{{ old('nomor_polisi', $fleet->nomor_polisi ?? '') }}" placeholder="Contoh: D 1234 AB" class="uppercase" required />
    </x-field>

    <x-field label="Jenis kendaraan" name="jenis_kendaraan" required>
        <x-input id="jenis_kendaraan" name="jenis_kendaraan" type="text" maxlength="50" value="{{ old('jenis_kendaraan', $fleet->jenis_kendaraan ?? '') }}" placeholder="Contoh: Truk Wingbox" required />
    </x-field>

    <x-field label="Kapasitas (kg)" name="kapasitas_kg" required>
        <x-input id="kapasitas_kg" name="kapasitas_kg" type="number" min="0.01" step="0.01" value="{{ old('kapasitas_kg', $fleet->kapasitas_kg ?? '') }}" placeholder="Contoh: 8000" required />
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
