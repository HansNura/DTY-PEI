@php
    $weight = old('berat_gram', $roll->berat_gram ?? '');
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
            if (gram >= 5350 && gram <= 5600) return 'AX';
            if (gram >= 5100 && gram < 5350) return 'AE';
            if (gram >= 1000 && gram < 5100) return 'A';
            if (gram >= 500 && gram < 1000) return 'B';
            return 'Di luar standar';
        }
     }">
    <div class="rounded-xl border border-slate-200 bg-slate-50/60 px-4 py-3 text-xs leading-relaxed text-slate-600">
        Isi sesuai label fisik bobbin. Grade bobot dihitung otomatis dari berat bersih, bukan dipilih manual.
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <x-field label="Denier (D)" name="denier" required hint="Angka pertama pada spesifikasi, mis. 150 pada DTY 150/48.">
            <x-input id="denier" name="denier" type="number" min="1" step="1" value="{{ old('denier', $roll->denier ?? '') }}" placeholder="Contoh: 150" required />
        </x-field>

        <x-field label="Jumlah filamen (F)" name="filamen" required hint="Angka kedua pada spesifikasi, mis. 48 pada DTY 150/48.">
            <x-input id="filamen" name="filamen" type="number" min="1" step="1" value="{{ old('filamen', $roll->filamen ?? '') }}" placeholder="Contoh: 48" required />
        </x-field>

        <x-field label="Berat bersih bobbin (gram)" name="berat_gram" required hint="Hanya berat benang, tanpa paper tube atau pembungkus.">
            <x-input id="berat_gram" name="berat_gram" type="number" min="0.01" step="0.01" x-model="weight" value="{{ $weight }}" placeholder="Contoh: 5485" required />
        </x-field>

        <x-field label="Lot / Merge No" name="nomor_lot" required hint="Samakan dengan nomor merge pada label kardus asal.">
            <x-input id="nomor_lot" name="nomor_lot" type="text" maxlength="50" value="{{ old('nomor_lot', $roll->nomor_lot ?? '') }}" placeholder="Contoh: 4D9164" required />
        </x-field>

        <div class="sm:col-span-2">
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
                               'text-slate-500': grade === null || grade === 'Di luar standar'
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
                                <td class="px-3 py-2">
                                    <span class="inline-flex items-center gap-2 font-semibold text-slate-700">
                                        <span class="size-1.5 rounded-full bg-emerald-500"></span>AX
                                    </span>
                                </td>
                                <td class="px-3 py-2 tabular-nums text-slate-600">5.350–5.600 g</td>
                                <td class="px-3 py-2 text-slate-500">Target penuh</td>
                            </tr>
                            <tr :class="grade === 'AE' ? 'bg-teal-50/70' : ''">
                                <td class="px-3 py-2">
                                    <span class="inline-flex items-center gap-2 font-semibold text-slate-700">
                                        <span class="size-1.5 rounded-full bg-teal-500"></span>AE
                                    </span>
                                </td>
                                <td class="px-3 py-2 tabular-nums text-slate-600">5.100–5.349 g</td>
                                <td class="px-3 py-2 text-slate-500">Sedikit di bawah standar</td>
                            </tr>
                            <tr :class="grade === 'A' ? 'bg-amber-50/70' : ''">
                                <td class="px-3 py-2">
                                    <span class="inline-flex items-center gap-2 font-semibold text-slate-700">
                                        <span class="size-1.5 rounded-full bg-amber-500"></span>A
                                    </span>
                                </td>
                                <td class="px-3 py-2 tabular-nums text-slate-600">1.000–5.099 g</td>
                                <td class="px-3 py-2 text-slate-500">Kualitas A, bobot terpotong</td>
                            </tr>
                            <tr :class="grade === 'B' ? 'bg-rose-50/70' : ''">
                                <td class="px-3 py-2">
                                    <span class="inline-flex items-center gap-2 font-semibold text-slate-700">
                                        <span class="size-1.5 rounded-full bg-rose-500"></span>B
                                    </span>
                                </td>
                                <td class="px-3 py-2 tabular-nums text-slate-600">500–999 g</td>
                                <td class="px-3 py-2 text-slate-500">Sisa / gulungan kecil</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <p class="mt-2 text-[11px] text-slate-500">AX/AE diprioritaskan; A dan B adalah rentang efektif setelah AX/AE.</p>
            </div>
        </div>
    </div>
</div>
