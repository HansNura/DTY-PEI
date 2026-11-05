@php
    $initialLatitude = old('latitude', $customer->latitude ?? '');
    $initialLongitude = old('longitude', $customer->longitude ?? '');
@endphp

<div class="space-y-6">
    <x-card title="Identitas pelanggan" description="Kode internal dan informasi perusahaan">
        <div class="grid gap-5 sm:grid-cols-2">
            <x-field label="Kode pelanggan" hint="Dibuat otomatis. Kode ini yang akan dipakai saat pelanggan disimpan.">
                <div class="flex items-center justify-between gap-3 rounded-xl border border-dashed border-teal-300 bg-teal-50/70 px-3.5 py-2.5">
                    <div class="flex min-w-0 items-center gap-2.5">
                        <span class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-white text-teal-700 ring-1 ring-teal-200">
                            <x-icon name="lock" class="size-3.5" />
                        </span>
                        <span class="truncate font-mono text-sm font-semibold tracking-wide text-teal-950">{{ $customer->kode_pelanggan ?? $previewCode ?? '—' }}</span>
                    </div>
                    <span class="shrink-0 rounded-full bg-teal-700 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-white">Otomatis</span>
                </div>
            </x-field>

            <x-field label="Nama pabrik pelanggan" name="nama_pabrik" required>
                <x-input id="nama_pabrik" name="nama_pabrik" type="text" maxlength="150" value="{{ old('nama_pabrik', $customer->nama_pabrik ?? '') }}" placeholder="Contoh: PT Contoh Tekstil" required />
            </x-field>

            <x-field label="Email perusahaan / kontak" name="email">
                <x-input id="email" name="email" type="email" maxlength="150" value="{{ old('email', $customer->email ?? '') }}" placeholder="purchasing@pabrik.co.id" />
            </x-field>

            <x-field label="NPWP" name="npwp" hint="Opsional">
                <x-input id="npwp" name="npwp" type="text" maxlength="30" value="{{ old('npwp', $customer->npwp ?? '') }}" placeholder="Contoh: 01.234.567.8-901.000" />
            </x-field>
        </div>
    </x-card>

    <x-card title="Alamat pengiriman" description="Alamat lengkap tujuan pengiriman">
        <div class="grid gap-5 sm:grid-cols-2">
            <x-field label="Alamat" name="alamat" required class="sm:col-span-2">
                <x-textarea id="alamat" name="alamat" rows="3" placeholder="Nama jalan, nomor, kelurahan, kecamatan" required>{{ old('alamat', $customer->alamat ?? '') }}</x-textarea>
            </x-field>

            <x-field label="Kota / Kabupaten" name="kota">
                <x-input id="kota" name="kota" type="text" maxlength="100" value="{{ old('kota', $customer->kota ?? '') }}" placeholder="Contoh: Bandung" />
            </x-field>

            <x-field label="Provinsi" name="provinsi">
                <x-input id="provinsi" name="provinsi" type="text" maxlength="100" value="{{ old('provinsi', $customer->provinsi ?? '') }}" placeholder="Contoh: Jawa Barat" />
            </x-field>

            <x-field label="Kode pos" name="kode_pos">
                <x-input id="kode_pos" name="kode_pos" type="text" maxlength="10" value="{{ old('kode_pos', $customer->kode_pos ?? '') }}" placeholder="Contoh: 40123" />
            </x-field>
        </div>
    </x-card>

    <x-card title="Kontak penerima" description="Orang yang dapat dihubungi saat pengiriman tiba">
        <div class="grid gap-5 sm:grid-cols-3">
            <x-field label="Nama kontak" name="kontak_nama" required>
                <x-input id="kontak_nama" name="kontak_nama" type="text" maxlength="100" value="{{ old('kontak_nama', $customer->kontak_nama ?? '') }}" placeholder="Contoh: Sari" required />
            </x-field>

            <x-field label="Jabatan" name="kontak_jabatan" hint="Opsional">
                <x-input id="kontak_jabatan" name="kontak_jabatan" type="text" maxlength="100" value="{{ old('kontak_jabatan', $customer->kontak_jabatan ?? '') }}" placeholder="Contoh: Purchasing" />
            </x-field>

            <x-field label="Telepon" name="kontak_telepon" required>
                <x-input id="kontak_telepon" name="kontak_telepon" type="tel" maxlength="30" value="{{ old('kontak_telepon', $customer->kontak_telepon ?? '') }}" placeholder="Contoh: 08123456789" required />
            </x-field>
        </div>
    </x-card>

    <x-card title="Titik lokasi pelanggan" description="Klik peta untuk memilih titik, atau geser marker untuk menyesuaikan.">
        <div class="overflow-hidden rounded-xl border border-slate-200">
            <div id="customer-map" class="map-embed h-80 w-full bg-slate-100" aria-label="Peta pemilihan lokasi pelanggan"></div>
        </div>

        <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
            <p class="text-xs text-slate-500">Koordinat dipakai untuk live tracking dan tautan peta.</p>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" data-customer-clear class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-medium text-slate-500 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700">
                    <x-icon name="x" class="size-4" />
                    Hapus titik
                </button>
            </div>
        </div>
        <p data-customer-map-status class="mt-1 text-xs text-slate-500" aria-live="polite"></p>

        <div class="mt-4 grid gap-5 sm:grid-cols-2">
            <x-field label="Latitude" name="latitude" hint="Terisi otomatis dari peta. Dapat diedit manual.">
                <x-input id="latitude" name="latitude" type="number" min="-90" max="90" step="any" value="{{ $initialLatitude }}" placeholder="Contoh: -6.9175" />
            </x-field>

            <x-field label="Longitude" name="longitude" hint="Terisi otomatis dari peta. Dapat diedit manual.">
                <x-input id="longitude" name="longitude" type="number" min="-180" max="180" step="any" value="{{ $initialLongitude }}" placeholder="Contoh: 107.6191" />
            </x-field>
        </div>
    </x-card>

    <x-card title="Catatan" description="Opsional, informasi tambahan untuk tim logistik">
        <x-textarea id="catatan" name="catatan" rows="2" maxlength="1000" aria-label="Catatan pelanggan">{{ old('catatan', $customer->catatan ?? '') }}</x-textarea>
    </x-card>
</div>

@push('scripts')
    @vite('resources/js/customer-map.js')
@endpush
