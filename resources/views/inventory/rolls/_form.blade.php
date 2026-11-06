@php
    $isEdit = isset($roll);
    $batch = $isEdit ? $roll->productionBatch : ($activeBatch ?? null);
    $weight = old('berat_gram', $isEdit ? $roll->berat_gram : '');
    $serialValue = old('nomor_seri', $isEdit ? $roll->nomor_seri : '');
@endphp

<div class="space-y-5"
     x-data="{
        serial: '{{ $serialValue }}',
        weight: '{{ $weight }}',
        get serialValid() {
            return /^[0-9]{17}$/.test(this.serial);
        },
        get serialDate() {
            const raw = this.serial.slice(9, 17);
            if (! /^[0-9]{8}$/.test(raw)) return '—';
            const date = new Date(raw.slice(0, 4) + '-' + raw.slice(4, 6) + '-' + raw.slice(6, 8) + 'T00:00:00');
            return Number.isNaN(date.getTime())
                ? '—'
                : date.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
        },
        get weightValue() {
            const value = parseFloat(this.weight);
            return Number.isFinite(value) ? value : null;
        },
        get grade() {
            const gram = this.weightValue;
            if (gram === null) return null;
            if (gram > 5600) return 'OW';
            if (gram < 500) return 'WASTE';
            if (gram >= 5350) return 'AX';
            if (gram >= 5100) return 'AE';
            if (gram >= 1000) return 'A';
            return 'B';
        }
     }">
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">Identitas bobbin</p>
            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-slate-500">17 digit · manual</span>
        </div>

        <div class="mt-3">
            <label for="nomor_seri" class="block text-sm font-medium text-slate-700">Nomor seri <span class="text-rose-600">*</span></label>
            <input id="nomor_seri" name="nomor_seri" type="text" inputmode="numeric" maxlength="17" pattern="[0-9]{17}"
                   x-model="serial" value="{{ $serialValue }}" placeholder="99054007920261103" required
                   class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 font-mono text-sm tracking-wide text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 focus:outline-none" />
            <p class="mt-1.5 text-xs text-slate-500">Isi satu field 17 digit. Sistem memecahnya menjadi komponen di bawah; nilai inilah yang menjadi payload QR.</p>
            <p x-show="serial.length > 0 && ! serialValid" x-cloak class="mt-1 text-xs font-medium text-rose-700">Nomor seri harus tepat 17 digit angka.</p>
        </div>

        <div class="mt-4 overflow-hidden rounded-xl border border-slate-200">
            <table class="w-full text-left text-[11px]" aria-label="Penjelasan komponen nomor seri">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col" class="px-3 py-2 font-semibold">Potongan</th>
                        <th scope="col" class="px-3 py-2 font-semibold">Digit</th>
                        <th scope="col" class="px-3 py-2 font-semibold">Nilai</th>
                        <th scope="col" class="px-3 py-2 font-semibold">Sumber data</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr>
                        <td class="px-3 py-2 font-semibold text-slate-700">2 digit awal</td>
                        <td class="px-3 py-2 text-slate-500">1–2</td>
                        <td class="px-3 py-2 font-mono font-semibold text-brand-700" x-text="serial.slice(0, 2) || '—'"></td>
                        <td class="px-3 py-2 text-slate-500">Kode area / lini produksi / modul DTY</td>
                    </tr>
                    <tr>
                        <td class="px-3 py-2 font-semibold text-slate-700">3 digit berikutnya</td>
                        <td class="px-3 py-2 text-slate-500">3–5</td>
                        <td class="px-3 py-2 font-mono font-semibold text-brand-700" x-text="serial.slice(2, 5) || '—'"></td>
                        <td class="px-3 py-2 text-slate-500">Nomor mesin / posisi spindel</td>
                    </tr>
                    <tr>
                        <td class="px-3 py-2 font-semibold text-slate-700">3 digit berikutnya</td>
                        <td class="px-3 py-2 text-slate-500">6–8</td>
                        <td class="px-3 py-2 font-mono font-semibold text-brand-700" x-text="serial.slice(5, 8) || '—'"></td>
                        <td class="px-3 py-2 text-slate-500">Nomor siklus doffing / section posisi</td>
                    </tr>
                    <tr>
                        <td class="px-3 py-2 font-semibold text-slate-700">1 digit</td>
                        <td class="px-3 py-2 text-slate-500">9</td>
                        <td class="px-3 py-2 font-mono font-semibold text-brand-700" x-text="serial.slice(8, 9) || '—'"></td>
                        <td class="px-3 py-2 text-slate-500">Kode shift kerja / sisi mesin</td>
                    </tr>
                    <tr>
                        <td class="px-3 py-2 font-semibold text-slate-700">8 digit akhir</td>
                        <td class="px-3 py-2 text-slate-500">10–17</td>
                        <td class="px-3 py-2 font-mono font-semibold text-brand-700">
                            <span x-text="serial.slice(9, 17) || '—'"></span>
                            <span class="mt-0.5 block font-sans text-[10px] font-normal text-slate-500" x-text="serialDate"></span>
                        </td>
                        <td class="px-3 py-2 text-slate-500">Tanggal produksi doffing (YYYYMMDD)</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p class="mt-2 text-[10px] text-slate-400">Contoh: 99054007920261103 → 99 · 054 · 007 · 9 · 20261103.</p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-slate-50/60 px-4 py-3">
        <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">Batch produksi</p>
        @if ($batch)
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <x-badge tone="brand">Merge {{ $batch->nomor_merge }}</x-badge>
                <span class="text-sm font-semibold text-slate-800">DTY {{ $batch->specification }}</span>
                <span class="text-xs text-slate-500">· produksi {{ $batch->production_date->translatedFormat('d M Y') }}</span>
            </div>
        @else
            <p class="mt-2 text-sm text-rose-700">Belum ada batch produksi aktif. Aktifkan batch sebelum menimbang bobbin.</p>
        @endif
    </div>

    <x-field label="Berat bersih bobbin (gram)" name="berat_gram" required hint="Rentang standar 500–5.600 gram. Di luar rentang masuk kategori anomali.">
        <x-input id="berat_gram" name="berat_gram" type="number" min="1" max="99999" step="0.01" x-model="weight" value="{{ $weight }}" placeholder="Contoh: 5485" required />
    </x-field>

    <div x-show="weightValue !== null && weightValue > 5600" x-cloak class="rounded-2xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        <p class="font-semibold">⚠️ Berat melebihi batas maksimum 5.600 g</p>
        <p class="mt-1 text-xs leading-relaxed">Bobbin masuk kategori <strong>Overweight / Hold (Grade OW)</strong>. Tidak boleh dipacking ke box reguler; tunggu keputusan QC atau rewind.</p>
    </div>

    <div x-show="weightValue !== null && weightValue < 500" x-cloak class="rounded-2xl border border-rose-300 bg-rose-50 px-4 py-3 text-sm text-rose-900">
        <p class="font-semibold">❌ Bobot di bawah minimum standar (&lt; 500 g)</p>
        <p class="mt-1 text-xs leading-relaxed">Bobbin masuk kategori <strong>Waste / Scrap</strong>. Label standar tidak dicetak; kumpulkan ke keranjang limbah untuk recycle.</p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">Grade bobot otomatis</p>
                <p class="mt-1 text-lg font-semibold"
                   :class="{
                       'text-emerald-700': grade === 'AX',
                       'text-teal-700': grade === 'AE',
                       'text-amber-700': grade === 'A',
                       'text-rose-700': grade === 'B',
                       'text-orange-700': grade === 'OW',
                       'text-red-700': grade === 'WASTE',
                       'text-slate-500': grade === null
                   }"
                   x-text="grade ?? 'Belum ditentukan'"></p>
            </div>
            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-slate-500">Otomatis</span>
        </div>

        <div class="mt-4 overflow-hidden rounded-xl border border-slate-200">
            <table class="w-full text-left text-[11px]" aria-label="Tabel standar grade bobot bobbin">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col" class="px-3 py-2 font-semibold">Grade</th>
                        <th scope="col" class="px-3 py-2 font-semibold">Rentang bobot</th>
                        <th scope="col" class="px-3 py-2 font-semibold">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr :class="grade === 'AX' ? 'bg-emerald-50/70' : ''">
                        <td class="px-3 py-2"><span class="inline-flex items-center gap-2 font-semibold text-slate-700"><span class="size-1.5 rounded-full bg-emerald-500"></span>AX</span></td>
                        <td class="px-3 py-2 tabular-nums text-slate-600">5.350–5.600 g</td>
                        <td class="px-3 py-2 text-slate-500">Target penuh</td>
                    </tr>
                    <tr :class="grade === 'AE' ? 'bg-teal-50/70' : ''">
                        <td class="px-3 py-2"><span class="inline-flex items-center gap-2 font-semibold text-slate-700"><span class="size-1.5 rounded-full bg-teal-500"></span>AE</span></td>
                        <td class="px-3 py-2 tabular-nums text-slate-600">5.100–5.349 g</td>
                        <td class="px-3 py-2 text-slate-500">Sedikit di bawah standar</td>
                    </tr>
                    <tr :class="grade === 'A' ? 'bg-amber-50/70' : ''">
                        <td class="px-3 py-2"><span class="inline-flex items-center gap-2 font-semibold text-slate-700"><span class="size-1.5 rounded-full bg-amber-500"></span>A</span></td>
                        <td class="px-3 py-2 tabular-nums text-slate-600">1.000–5.099 g</td>
                        <td class="px-3 py-2 text-slate-500">Kualitas A, bobot terpotong</td>
                    </tr>
                    <tr :class="grade === 'B' ? 'bg-rose-50/70' : ''">
                        <td class="px-3 py-2"><span class="inline-flex items-center gap-2 font-semibold text-slate-700"><span class="size-1.5 rounded-full bg-rose-500"></span>B</span></td>
                        <td class="px-3 py-2 tabular-nums text-slate-600">500–999 g</td>
                        <td class="px-3 py-2 text-slate-500">Sisa / gulungan kecil</td>
                    </tr>
                    <tr :class="grade === 'OW' ? 'bg-amber-50/70' : ''">
                        <td class="px-3 py-2"><span class="inline-flex items-center gap-2 font-semibold text-slate-700"><span class="size-1.5 rounded-full bg-orange-500"></span>OW</span></td>
                        <td class="px-3 py-2 tabular-nums text-slate-600">&gt; 5.600 g</td>
                        <td class="px-3 py-2 text-slate-500">Overweight / hold, tidak untuk box reguler</td>
                    </tr>
                    <tr :class="grade === 'WASTE' ? 'bg-rose-50/70' : ''">
                        <td class="px-3 py-2"><span class="inline-flex items-center gap-2 font-semibold text-slate-700"><span class="size-1.5 rounded-full bg-red-600"></span>WASTE</span></td>
                        <td class="px-3 py-2 tabular-nums text-slate-600">&lt; 500 g</td>
                        <td class="px-3 py-2 text-slate-500">Scrap / limbah, tanpa label standar</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="mt-2 text-[11px] text-slate-500">AX/AE diprioritaskan; A dan B adalah rentang efektif setelah AX/AE. OW dan WASTE adalah anomali di luar standar.</p>
    </div>

    @if ($isEdit)
        <div class="rounded-xl bg-slate-50 px-4 py-3 text-xs text-slate-500">
            Nomor seri, merge, dan spesifikasi tidak berubah saat berat dikoreksi.
        </div>
    @endif
</div>
