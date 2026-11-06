@php
    $isEdit = isset($roll);
    $batch = $isEdit ? $roll->productionBatch : ($activeBatch ?? null);
    $weight = old('berat_gram', $isEdit ? $roll->berat_gram : '');
@endphp

<div class="space-y-5"
     x-data="{
        weight: '{{ $weight }}',
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
    @if ($isEdit)
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">Identitas bobbin</p>
            <dl class="mt-3 grid gap-3 sm:grid-cols-2">
                <div>
                    <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Nomor seri</dt>
                    <dd class="mt-1 font-mono text-sm font-semibold tracking-wide text-slate-800">{{ $roll->nomor_seri ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Kode rol</dt>
                    <dd class="mt-1 font-mono text-xs font-medium text-slate-700">{{ $roll->kode_rol }}</dd>
                </div>
            </dl>
        </div>
    @elseif (! empty($previewSerial))
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">Identitas bobbin</p>
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-slate-500">Pratinjau otomatis</span>
            </div>
            <dl class="mt-3 grid gap-3 sm:grid-cols-2">
                <div>
                    <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Nomor seri</dt>
                    <dd class="mt-1 font-mono text-sm font-semibold tracking-wide text-slate-800">{{ old('nomor_seri', $previewSerial) }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Kode rol</dt>
                    <dd class="mt-1 font-mono text-xs font-medium text-slate-700">{{ old('kode_rol', $previewKodeRol) }}</dd>
                </div>
            </dl>
            <input type="hidden" name="nomor_seri" value="{{ old('nomor_seri', $previewSerial) }}">
            <input type="hidden" name="kode_rol" value="{{ old('kode_rol', $previewKodeRol) }}">
            <p class="mt-2 text-[10px] text-slate-400">Nilai ini dipakai saat bobbin disimpan.</p>
        </div>
    @endif

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
            Kode rol, nomor seri, merge, dan spesifikasi tidak berubah saat berat dikoreksi.
        </div>
    @endif
</div>
