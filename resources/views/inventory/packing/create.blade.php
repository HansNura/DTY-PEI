@extends('layouts.app')

@section('title', 'Packing Kardus')

@section('content')
    <div class="mx-auto max-w-6xl space-y-6"
         x-data="{
            summary: @js($summary),
            token: '',
            loading: false,
            errorMessage: '',
            successMessage: '',
            cameraActive: false,
            cameraError: '',
            stream: null,
            detector: null,
            cameraTimer: null,

            get canFinalize() {
                return this.summary.count === 6;
            },

            async scanToken() {
                if (this.loading || this.token.trim() === '') return;

                this.loading = true;
                this.errorMessage = '';
                this.successMessage = '';

                try {
                    const response = await fetch('{{ route('warehouse.packing.scan') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ token: this.token.trim() }),
                    });

                    const data = await response.json();
                    this.summary = data.summary;
                    this.token = '';

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
                    const response = await fetch('/warehouse/packing/scan/' + id, {
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
                    const response = await fetch('{{ route('warehouse.packing.reset') }}', {
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
        <x-page-header title="Packing kardus" description="Scan 6 bobbin dengan merge dan grade yang sama. Net weight, unit, grade, HU, dan tanggal dihitung otomatis.">
            <x-slot:actions>
                <x-button href="{{ route('warehouse.cartons.index') }}" variant="ghost" icon="boxes">Kardus DTY</x-button>
                <x-button href="{{ route('warehouse.rolls.index') }}" variant="ghost" icon="package">Stok bobbin</x-button>
            </x-slot:actions>
        </x-page-header>

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
            <x-card title="Scan bobbin" description="Scan QR serial atau ketik nomor seri, lalu tekan Enter.">
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

                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <x-button type="button" variant="secondary" size="sm" icon="camera" x-on:click="startCamera()" x-show="! cameraActive">Buka kamera</x-button>
                    <x-button type="button" variant="secondary" size="sm" icon="x" x-on:click="stopCamera()" x-show="cameraActive" x-cloak>Matikan kamera</x-button>
                    <x-button type="button" variant="ghost" size="sm" icon="refresh" x-on:click="resetPacking()" x-bind:disabled="summary.count === 0">Kosongkan</x-button>
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
                            <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Spesifikasi</dt>
                            <dd class="mt-1 text-sm font-semibold text-slate-800" x-text="summary.specification ?? '—'"></dd>
                        </div>
                        <div class="col-span-2 rounded-xl bg-brand-50 px-3.5 py-3 ring-1 ring-inset ring-brand-100">
                            <dt class="text-[10px] font-medium uppercase tracking-wide text-brand-700">Net weight sementara</dt>
                            <dd class="mt-1 text-lg font-semibold tabular-nums text-brand-800"><span x-text="Number(summary.net_kg).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })"></span> kg</dd>
                        </div>
                    </dl>

                    <form method="POST" action="{{ route('warehouse.packing.store') }}" class="mt-5 space-y-4">
                        @csrf
                        <x-field label="Gross weight (kg)" name="gross_weight_kg" required hint="Timbang kardus + isi + lakban.">
                            <x-input id="gross_weight_kg" name="gross_weight_kg" type="number" min="0.01" step="0.01" value="{{ old('gross_weight_kg') }}" placeholder="Contoh: 35.56" required />
                        </x-field>
                        <x-field label="Nomor pallet" name="pallet_number" hint="Opsional">
                            <x-input id="pallet_number" name="pallet_number" type="text" maxlength="50" value="{{ old('pallet_number') }}" placeholder="Contoh: ADP063296" class="font-mono uppercase" />
                        </x-field>
                        <x-field label="Catatan" name="catatan" hint="Opsional">
                            <x-textarea id="catatan" name="catatan" rows="2" maxlength="500">{{ old('catatan') }}</x-textarea>
                        </x-field>

                        <div class="rounded-xl bg-slate-50 px-3.5 py-3 text-xs text-slate-500">
                            HU dan tanggal produksi dibuat otomatis saat packing diselesaikan.
                        </div>

                        <x-button type="submit" icon="check" class="w-full justify-center" x-bind:disabled="! canFinalize" x-bind:class="! canFinalize ? 'opacity-50' : ''">
                            Selesaikan packing
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
@endsection
