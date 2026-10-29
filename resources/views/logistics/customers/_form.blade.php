<div class="grid gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label for="nama_pabrik" class="block text-sm font-medium text-slate-700">Nama pabrik pelanggan</label>
        <input id="nama_pabrik" name="nama_pabrik" type="text" maxlength="150" value="{{ old('nama_pabrik', $customer->nama_pabrik ?? '') }}" required
               class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
    </div>
    <div class="sm:col-span-2">
        <label for="alamat" class="block text-sm font-medium text-slate-700">Alamat pengiriman</label>
        <textarea id="alamat" name="alamat" rows="3" required class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">{{ old('alamat', $customer->alamat ?? '') }}</textarea>
    </div>
    <div>
        <label for="kontak_nama" class="block text-sm font-medium text-slate-700">Nama kontak penerima</label>
        <input id="kontak_nama" name="kontak_nama" type="text" maxlength="100" value="{{ old('kontak_nama', $customer->kontak_nama ?? '') }}" required
               class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
    </div>
    <div>
        <label for="kontak_telepon" class="block text-sm font-medium text-slate-700">Telepon penerima</label>
        <input id="kontak_telepon" name="kontak_telepon" type="tel" maxlength="30" value="{{ old('kontak_telepon', $customer->kontak_telepon ?? '') }}" required
               class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
    </div>
    <div>
        <label for="latitude" class="block text-sm font-medium text-slate-700">Latitude (opsional)</label>
        <input id="latitude" name="latitude" type="number" min="-90" max="90" step="any" value="{{ old('latitude', $customer->latitude ?? '') }}"
               class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
    </div>
    <div>
        <label for="longitude" class="block text-sm font-medium text-slate-700">Longitude (opsional)</label>
        <input id="longitude" name="longitude" type="number" min="-180" max="180" step="any" value="{{ old('longitude', $customer->longitude ?? '') }}"
               class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
    </div>
    <p class="text-xs text-slate-500 sm:col-span-2">Isi latitude dan longitude bersama-sama. Koordinat akan ditautkan ke Google Maps.</p>
</div>
