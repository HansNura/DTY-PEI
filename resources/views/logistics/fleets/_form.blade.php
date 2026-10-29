<div class="grid gap-5 sm:grid-cols-2">
    <div>
        <label for="nomor_polisi" class="block text-sm font-medium text-slate-700">Nomor polisi</label>
        <input id="nomor_polisi" name="nomor_polisi" type="text" maxlength="20" value="{{ old('nomor_polisi', $fleet->nomor_polisi ?? '') }}" required
               class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm uppercase shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
    </div>
    <div>
        <label for="jenis_kendaraan" class="block text-sm font-medium text-slate-700">Jenis kendaraan</label>
        <input id="jenis_kendaraan" name="jenis_kendaraan" type="text" maxlength="50" value="{{ old('jenis_kendaraan', $fleet->jenis_kendaraan ?? '') }}" required
               class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
    </div>
    <div>
        <label for="kapasitas_kg" class="block text-sm font-medium text-slate-700">Kapasitas (kg)</label>
        <input id="kapasitas_kg" name="kapasitas_kg" type="number" min="0.01" step="0.01" value="{{ old('kapasitas_kg', $fleet->kapasitas_kg ?? '') }}" required
               class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
    </div>
    <div>
        <label for="driver_id" class="block text-sm font-medium text-slate-700">Pengemudi utama (opsional)</label>
        <select id="driver_id" name="driver_id" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
            <option value="">Belum ditentukan</option>
            @foreach ($drivers as $driver)
                <option value="{{ $driver->id }}" @selected((string) old('driver_id', $fleet->driver_id ?? '') === (string) $driver->id)>{{ $driver->nama }}</option>
            @endforeach
        </select>
        @if ($drivers->isEmpty())
            <p class="mt-1 text-xs text-slate-500">Belum ada akun driver aktif yang dapat dipilih.</p>
        @endif
    </div>
</div>
