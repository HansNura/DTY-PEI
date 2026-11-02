<div class="space-y-6">
    <div class="grid gap-5 sm:grid-cols-2">
        <x-field label="Nama pabrik pelanggan" name="nama_pabrik" required class="sm:col-span-2">
            <x-input id="nama_pabrik" name="nama_pabrik" type="text" maxlength="150" value="{{ old('nama_pabrik', $customer->nama_pabrik ?? '') }}" required />
        </x-field>

        <x-field label="Alamat pengiriman" name="alamat" required class="sm:col-span-2">
            <x-textarea id="alamat" name="alamat" rows="3" required>{{ old('alamat', $customer->alamat ?? '') }}</x-textarea>
        </x-field>

        <x-field label="Nama kontak penerima" name="kontak_nama" required>
            <x-input id="kontak_nama" name="kontak_nama" type="text" maxlength="100" value="{{ old('kontak_nama', $customer->kontak_nama ?? '') }}" required />
        </x-field>

        <x-field label="Telepon penerima" name="kontak_telepon" required>
            <x-input id="kontak_telepon" name="kontak_telepon" type="tel" maxlength="30" value="{{ old('kontak_telepon', $customer->kontak_telepon ?? '') }}" required />
        </x-field>

        <x-field label="Latitude" name="latitude" hint="Opsional. Isi bersama longitude.">
            <x-input id="latitude" name="latitude" type="number" min="-90" max="90" step="any" value="{{ old('latitude', $customer->latitude ?? '') }}" />
        </x-field>

        <x-field label="Longitude" name="longitude" hint="Opsional. Isi bersama latitude.">
            <x-input id="longitude" name="longitude" type="number" min="-180" max="180" step="any" value="{{ old('longitude', $customer->longitude ?? '') }}" />
        </x-field>
    </div>

    <p class="rounded-xl bg-slate-50 px-4 py-3 text-xs text-slate-500">
        Koordinat akan ditautkan ke Google Maps dan membantu memperkirakan posisi tujuan pada live tracking.
    </p>
</div>
