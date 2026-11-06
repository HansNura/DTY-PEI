    <div class="mx-auto max-w-6xl space-y-6"
         x-data="{
            summary: @js($summary),
            availableRolls: @js($availableRolls->map(fn ($roll) => [
                'id' => $roll->id,
                'nomor_seri' => $roll->nomor_seri,
                'berat_gram' => (float) $roll->berat_gram,
                'grade' => $roll->grade,
                'nomor_lot' => $roll->nomor_lot,
            ])->values()),
            token: '',
            loading: false,
            errorMessage: '',
            successMessage: '',
            manualOpen: true,
            manualSearch: '',
            manualGrade: @js($summary['grade']),
            manualMerge: @js($summary['merge']),
            cameraActive: false,
            cameraError: '',
            stream: null,
            detector: null,
            cameraTimer: null,

            get canFinalize() {
                return this.summary.count === 6;
            },

            get manualFilterReady() {
                return this.summary.grade !== null
                    || (this.manualGrade !== null && this.manualGrade !== '');
            },

            get manualGrades() {
                const order = { AX: 0, AE: 1, A: 2, B: 3 };
                const merge = this.summary.merge ?? this.manualMerge;

                return [...new Set(this.availableRolls
                    .filter((roll) => ! merge || roll.nomor_lot === merge)
                    .map((roll) => roll.grade)
                    .filter(Boolean))]
                    .sort((a, b) => (order[a] ?? 9) - (order[b] ?? 9));
            },

            get manualMerges() {
                const grade = this.summary.grade ?? this.manualGrade;

                return [...new Set(this.availableRolls
                    .filter((roll) => ! grade || roll.grade === grade)
                    .map((roll) => roll.nomor_lot)
                    .filter(Boolean))]
                    .sort();
            },

            get filteredManualRolls() {
                if (! this.manualFilterReady) return [];

                const grade = this.summary.grade ?? this.manualGrade;
                const merge = this.summary.merge ?? this.manualMerge;
                const search = this.manualSearch.trim().toLowerCase();

                return this.availableRolls.filter((roll) => {
                    if (grade && roll.grade !== grade) return false;
                    if (merge && roll.nomor_lot !== merge) return false;
                    if (search && ! `${roll.nomor_seri} ${roll.nomor_lot}`.toLowerCase().includes(search)) return false;

                    return true;
                });
            },

            isInSummary(id) {
                return this.summary.rolls.some((roll) => roll.id === id);
            },

            syncManualFilters() {
                if (this.summary.grade) this.manualGrade = this.summary.grade;
                if (this.summary.merge) this.manualMerge = this.summary.merge;
            },

            async scanToken(token = null) {
                const value = (token ?? this.token).trim();

                if (this.loading || value === '') return;

                this.loading = true;
                this.errorMessage = '';
                this.successMessage = '';

                try {
                    const response = await fetch('{{ $scanUrl }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ token: value }),
                    });

                    const data = await response.json();
                    this.summary = data.summary;
                    this.token = '';
                    this.syncManualFilters();

                    if (data.success) {
                        this.successMessage = data.message;
                        this.$nextTick(() => this.$refs.tokenInput?.focus());
                    } else {
                        this.errorMessage = data.message;
                    }
                } catch (error) {
                    this.errorMessage = 'Gagal terhubung ke server.';
                } finally {
                    this.loading = false;
                }
            },

            async removeRoll(id) {
                this.errorMessage = '';
                this.successMessage = '';

                try {
                    const response = await fetch('{{ $removeUrlBase }}/' + id, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'Accept': 'application/json',
                        },
                    });
                    const data = await response.json();
                    this.summary = data.summary;
                    this.successMessage = data.message;
                } catch (error) {
                    this.errorMessage = 'Gagal menghapus bobbin dari daftar.';
                }
            },

            async resetPacking() {
                this.errorMessage = '';
                this.successMessage = '';

                try {
                    const response = await fetch('{{ $resetUrl }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'Accept': 'application/json',
                        },
                    });
                    const data = await response.json();
                    this.summary = data.summary;
                    this.successMessage = data.message;
                } catch (error) {
                    this.errorMessage = 'Gagal mengosongkan daftar packing.';
                }
            },

            async startCamera() {
                this.cameraError = '';

                if (! ('BarcodeDetector' in window)) {
                    this.cameraError = 'Browser ini belum mendukung scan kamera. Gunakan scanner USB atau ketik serial.';
                    return;
                }

                try {
                    this.detector = new BarcodeDetector({ formats: ['qr_code'] });
                    this.stream = await navigator.mediaDevices.getUserMedia({
                        video: { facingMode: { ideal: 'environment' } },
                        audio: false,
                    });
                    this.$refs.camera.srcObject = this.stream;
                    await this.$refs.camera.play();
                    this.cameraActive = true;
                    this.cameraTimer = setInterval(() => this.detectFrame(), 500);
                } catch (error) {
                    this.cameraError = 'Kamera tidak dapat diakses. Periksa izin kamera.';
                    this.stopCamera();
                }
            },

            async detectFrame() {
                if (! this.cameraActive || this.loading || ! this.detector || ! this.$refs.camera) return;
                if (this.$refs.camera.readyState < 2) return;

                try {
                    const codes = await this.detector.detect(this.$refs.camera);
                    if (codes.length > 0) {
                        this.stopCamera();
                        this.token = codes[0].rawValue;
                        await this.scanToken();
                    }
                } catch (error) {
                    // Coba frame berikutnya.
                }
            },

            stopCamera() {
                if (this.cameraTimer) clearInterval(this.cameraTimer);
                this.cameraTimer = null;
                this.cameraActive = false;
                if (this.stream) {
                    this.stream.getTracks().forEach((track) => track.stop());
                    this.stream = null;
                }
            }
         }">
        <x-page-header :title="$pageTitle" :description="$carton ? $carton->nomor_hu.' · Merge '.$carton->nomor_merge.' — '.$pageDescription : $pageDescription">
            <x-slot:actions>
                @if ($carton)
                    <x-button href="{{ route('warehouse.cartons.label', $carton) }}" variant="secondary" icon="printer">Label kardus</x-button>
                    <x-button href="{{ route('warehouse.cartons.index') }}" variant="ghost" icon="arrow-left">Kembali</x-button>
                @else
                    <x-button href="{{ route('warehouse.cartons.index') }}" variant="ghost" icon="boxes">Kardus DTY</x-button>
                    <x-button href="{{ route('warehouse.rolls.index') }}" variant="ghost" icon="package">Stok bobbin</x-button>
                @endif
            </x-slot:actions>
        </x-page-header>

        @if ($carton)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <p class="font-semibold">Mode edit isi kardus</p>
                <p class="mt-1 text-xs leading-relaxed">Keluarkan bobbin yang ingin diganti, lalu scan bobbin pengganti dengan merge dan grade yang sama. Bobbin lama yang dikeluarkan akan kembali berstatus siap packing saat disimpan.</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
            <x-card title="Scan bobbin" description="Scan QR atau ketik nomor seri 17 digit, lalu tekan Enter.">
                <x-slot:actions>
                    <x-badge tone="neutral">Target 6 bobbin</x-badge>
                </x-slot:actions>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <div class="relative min-w-0 flex-1">
                        <x-icon name="scan" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                        <input x-ref="tokenInput" x-model="token" type="text" placeholder="Scan QR / nomor seri bobbin" aria-label="Scan QR atau nomor seri bobbin"
                               class="block w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 transition focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 focus:outline-none"
                               x-on:keydown.enter.prevent="scanToken()" />
                    </div>
                    <x-button type="button" icon="scan" x-on:click="scanToken()" x-bind:disabled="loading">
                        <span x-show="! loading">Scan</span>
                        <span x-show="loading" x-cloak>Memproses...</span>
                    </x-button>
                </div>

                <p class="mt-2 text-[11px] text-slate-400">Format nomor seri: 2 digit area · 3 digit mesin · 3 digit doffing · 1 digit shift · 8 digit tanggal.</p>

                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <x-button type="button" variant="secondary" size="sm" icon="plus" x-on:click="manualOpen = ! manualOpen">
                        <span x-text="manualOpen ? 'Tutup daftar' : 'Tambah manual'"></span>
                    </x-button>
                    <x-button type="button" variant="secondary" size="sm" icon="camera" x-on:click="startCamera()" x-show="! cameraActive">Buka kamera</x-button>
                    <x-button type="button" variant="secondary" size="sm" icon="x" x-on:click="stopCamera()" x-show="cameraActive" x-cloak>Matikan kamera</x-button>
                    <x-button type="button" variant="ghost" size="sm" icon="refresh" x-on:click="resetPacking()" x-bind:disabled="summary.count === 0">{{ $carton ? 'Kembalikan isi awal' : 'Kosongkan' }}</x-button>
                </div>

                <div x-show="manualOpen" x-cloak class="mt-3 rounded-2xl border border-slate-200 bg-slate-50/60 p-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-xs font-semibold text-slate-700">Pilih bobbin manual</p>
                        <span class="text-[10px] text-slate-400">Hanya bobbin siap packing</span>
                    </div>

                    <div class="mt-2 grid gap-2 sm:grid-cols-[minmax(0,1fr)_140px_140px]">
                        <input x-model="manualSearch" type="search" placeholder="Cari nomor seri atau lot" aria-label="Cari bobbin manual"
                               class="block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 focus:outline-none" />

                        <select x-model="manualGrade" :disabled="summary.grade !== null" aria-label="Filter grade bobbin manual"
                                class="block w-full rounded-xl border border-slate-200 bg-white px-2.5 py-2 text-xs text-slate-700 shadow-sm disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 focus:outline-none">
                            <option value="">Pilih grade</option>
                            <template x-for="grade in manualGrades" :key="grade">
                                <option :value="grade" x-text="grade"></option>
                            </template>
                        </select>

                        <select x-model="manualMerge" :disabled="summary.merge !== null" aria-label="Filter merge bobbin manual"
                                class="block w-full rounded-xl border border-slate-200 bg-white px-2.5 py-2 text-xs text-slate-700 shadow-sm disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 focus:outline-none">
                            <option value="">Semua merge</option>
                            <template x-for="merge in manualMerges" :key="merge">
                                <option :value="merge" x-text="merge"></option>
                            </template>
                        </select>
                    </div>

                    <p x-show="summary.grade || summary.merge" x-cloak class="mt-2 text-[10px] text-slate-500">Grade dan merge terkunci mengikuti bobbin yang sudah dipilih. Bobbin dengan grade berbeda tidak ditampilkan.</p>
                    <p x-show="! manualFilterReady" x-cloak class="mt-2 text-[10px] text-amber-700">Pilih grade terlebih dahulu untuk menampilkan bobbin.</p>

                    <div x-show="manualFilterReady" class="mt-2 max-h-64 divide-y divide-slate-100 overflow-y-auto rounded-xl border border-slate-200 bg-white">
                        <template x-for="roll in filteredManualRolls" :key="roll.id">
                            <div class="flex items-center justify-between gap-3 px-3 py-2.5">
                                <div class="min-w-0">
                                    <p class="font-mono text-xs font-semibold text-slate-800" x-text="roll.nomor_seri"></p>
                                    <p class="mt-0.5 text-[10px] text-slate-500">Lot <span x-text="roll.nomor_lot"></span> · <span x-text="Number(roll.berat_gram).toLocaleString('id-ID')"></span> g · Grade <span class="font-semibold" x-text="roll.grade"></span></p>
                                </div>
                                <x-button type="button" variant="secondary" size="sm" x-on:click="scanToken(roll.nomor_seri)" x-bind:disabled="loading || summary.count >= 6 || isInSummary(roll.id)">
                                    <span x-text="isInSummary(roll.id) ? 'Ditambahkan' : (summary.count >= 6 ? 'Penuh' : 'Tambah')"></span>
                                </x-button>
                            </div>
                        </template>
                        <p x-show="filteredManualRolls.length === 0" class="px-3 py-4 text-center text-xs text-slate-500">Tidak ada bobbin siap packing dengan grade dan merge ini.</p>
                    </div>
                </div>

                <div x-show="cameraActive" x-cloak class="mt-3 overflow-hidden rounded-xl bg-slate-950">
                    <video x-ref="camera" class="h-56 w-full object-cover" playsinline muted></video>
                </div>

                <p x-show="cameraError" x-cloak class="mt-2 text-xs text-rose-700" x-text="cameraError"></p>
                <p x-show="errorMessage" x-cloak class="mt-2 rounded-xl bg-rose-50 px-3 py-2 text-xs font-medium text-rose-700" x-text="errorMessage"></p>
                <p x-show="successMessage" x-cloak class="mt-2 rounded-xl bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-700" x-text="successMessage"></p>

                <div class="mt-5 flex items-center justify-between gap-3">
                    <p class="text-xs text-slate-500">Bobbin dalam daftar</p>
                    <p class="text-sm font-semibold text-slate-800"><span x-text="summary.count"></span> / 6</p>
                </div>
                <div class="mt-2 flex gap-1.5">
                    <template x-for="i in 6" :key="i">
                        <span class="h-1.5 flex-1 rounded-full" :class="i <= summary.count ? 'bg-brand-500' : 'bg-slate-200'"></span>
                    </template>
                </div>

                <ul class="mt-4 divide-y divide-slate-100">
                    <template x-for="roll in summary.rolls" :key="roll.id">
                        <li class="flex items-center justify-between gap-3 py-3">
                            <div class="min-w-0">
                                <p class="font-mono text-xs font-semibold text-slate-800" x-text="roll.nomor_seri"></p>
                                <p class="mt-0.5 font-mono text-[10px] tracking-wide text-slate-400" x-text="[(roll.nomor_seri ?? '').slice(0, 2), (roll.nomor_seri ?? '').slice(2, 5), (roll.nomor_seri ?? '').slice(5, 8), (roll.nomor_seri ?? '').slice(8, 9), (roll.nomor_seri ?? '').slice(9, 17)].join(' · ')"></p>
                                <span x-show="roll.current" x-cloak class="mt-1 inline-flex rounded-full bg-brand-50 px-2 py-0.5 text-[10px] font-medium text-brand-700 ring-1 ring-inset ring-brand-100">Bobbin kardus ini</span>
                                <p class="mt-0.5 text-[11px] text-slate-500">
                                    <span x-text="Number(roll.berat_gram).toLocaleString('id-ID')"></span> g
                                    · Grade <span class="font-semibold" x-text="roll.grade ?? '—'"></span>
                                    · Merge <span class="font-mono" x-text="roll.nomor_lot"></span>
                                </p>
                            </div>
                            <button type="button" class="rounded-lg p-1.5 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600" x-on:click="removeRoll(roll.id)" aria-label="Hapus bobbin dari daftar">
                                <x-icon name="x" class="size-4" />
                            </button>
                        </li>
                    </template>
                </ul>

                <div x-show="summary.count === 0" x-cloak class="rounded-xl border border-dashed border-slate-200 bg-slate-50/60 px-4 py-8 text-center">
                    <x-icon name="scan" class="mx-auto size-7 text-slate-300" />
                    <p class="mt-2 text-sm font-medium text-slate-600">Belum ada bobbin discan</p>
                    <p class="mt-1 text-xs text-slate-500">Scan 6 bobbin dari merge dan grade yang sama.</p>
                </div>
            </x-card>

            <div class="space-y-6">
                <x-card title="Ringkasan packing">
                    <dl class="grid grid-cols-2 gap-3">
                        <div class="rounded-xl bg-slate-50 px-3.5 py-3 ring-1 ring-inset ring-slate-100">
                            <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Merge</dt>
                            <dd class="mt-1 font-mono text-sm font-semibold text-slate-800" x-text="summary.merge ?? '—'"></dd>
                        </div>
                        <div class="rounded-xl bg-slate-50 px-3.5 py-3 ring-1 ring-inset ring-slate-100">
                            <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Grade</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-800" x-text="summary.grade ?? '—'"></dd>
                        </div>
                        <div class="col-span-2 rounded-xl bg-slate-50 px-3.5 py-3 ring-1 ring-inset ring-slate-100">
                            <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Tanggal produksi batch</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-800" x-text="summary.production_date ?? '—'"></dd>
                        </div>
                        <div class="col-span-2 rounded-xl bg-slate-50 px-3.5 py-3 ring-1 ring-inset ring-slate-100">
                            <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Spesifikasi</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-800" x-text="summary.specification ?? '—'"></dd>
                        </div>
                        <div class="col-span-2 rounded-xl bg-brand-50 px-3.5 py-3 ring-1 ring-inset ring-brand-100">
                            <dt class="text-[10px] font-medium uppercase tracking-wide text-brand-700">Net weight sementara</dt>
                            <dd class="mt-1 text-lg font-semibold tabular-nums text-brand-800"><span x-text="Number(summary.net_kg).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })"></span> kg</dd>
                        </div>
                    </dl>

                    <form method="POST" action="{{ $submitUrl }}" class="mt-5 space-y-4">
                        @csrf
                        @if ($carton)
                            @method('PUT')
                        @endif
                        <x-field label="Gross weight (kg)" name="gross_weight_kg" required hint="Timbang kardus + isi + lakban.">
                            <x-input id="gross_weight_kg" name="gross_weight_kg" type="number" min="0.01" step="0.01" value="{{ old('gross_weight_kg', $carton?->gross_weight_kg) }}" placeholder="Contoh: 35.56" required />
                        </x-field>
                        <x-field label="Catatan" name="catatan" hint="Opsional">
                            <x-textarea id="catatan" name="catatan" rows="2" maxlength="500">{{ old('catatan', $carton?->catatan) }}</x-textarea>
                        </x-field>

                        <div class="rounded-xl bg-slate-50 px-3.5 py-3 text-xs text-slate-500">
                            {{ $carton ? 'HU dan tanggal produksi tetap; net, unit, dan komposisi bobbin dihitung ulang.' : 'HU dan tanggal produksi dibuat otomatis saat packing diselesaikan.' }}
                        </div>

                        <x-button type="submit" icon="check" class="w-full justify-center" x-bind:disabled="! canFinalize" x-bind:class="! canFinalize ? 'opacity-50' : ''">
                            {{ $submitLabel }}
                        </x-button>
                        <p x-show="! canFinalize" class="text-center text-[11px] text-slate-400">Lengkapi 6 bobbin terlebih dahulu.</p>
                    </form>
                </x-card>

                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-relaxed text-amber-900">
                    <p class="font-semibold">Aturan packing</p>
                    <ul class="mt-1 list-disc space-y-0.5 pl-4">
                        <li>6 bobbin dalam satu kardus harus punya merge dan grade yang sama.</li>
                        <li>Bobbin yang sudah dipacking tidak bisa discan ke kardus lain.</li>
                        <li>Grade AX 6 bobbin wajib memiliki gross 34,74–36,25 kg.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
