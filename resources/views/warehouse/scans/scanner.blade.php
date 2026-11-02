@extends('layouts.app')

@section('title', 'Scanner — ' . $order->nomor_do)

@section('content')
    <div class="mx-auto max-w-6xl space-y-6"
         x-data="{
            loading: false,
            lastResult: null,
            history: [],
            loadedCount: {{ $order->items->filter(fn ($item) => $item->dtyRoll?->status === \App\Enums\RollStatus::Loaded)->count() }},
            totalRol: {{ $order->items->count() }},
            cameraActive: false,
            cameraError: null,
            stream: null,
            detector: null,
            cameraTimer: null,

            async submitToken(token) {
                if (this.loading || !token.trim()) return;
                this.loading = true;
                try {
                    const resp = await fetch('{{ route('warehouse.scans.scan', $order) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ qr_token: token.trim() }),
                    });
                    const data = await resp.json();
                    this.lastResult = data;
                    this.loadedCount = data.loaded_count;
                    this.history.unshift({
                        time: new Date().toLocaleTimeString('id-ID'),
                        roll: data.roll?.kode_rol ?? '—',
                        lot: data.roll?.nomor_lot ?? '—',
                        result: data.result,
                        result_label: data.result_label,
                        message: data.message,
                    });
                    this.$refs.tokenInput.value = '';
                    this.$refs.tokenInput.focus();
                } catch (error) {
                    this.lastResult = { success: false, message: 'Gagal terhubung ke server.', result: 'unknown' };
                } finally {
                    this.loading = false;
                }
            },

            async startCamera() {
                this.cameraError = null;

                if (! ('BarcodeDetector' in window)) {
                    this.cameraError = 'Browser ini belum mendukung pemindaian kamera. Gunakan scanner USB atau ketik token manual.';
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
                    this.cameraError = 'Kamera tidak dapat diakses. Periksa izin kamera atau gunakan scanner manual.';
                    this.stopCamera();
                }
            },

            async detectFrame() {
                if (! this.cameraActive || this.loading || ! this.detector || ! this.$refs.camera) return;
                if (this.$refs.camera.readyState < 2) return;

                try {
                    const codes = await this.detector.detect(this.$refs.camera);
                    if (codes.length > 0) {
                        const token = codes[0].rawValue;
                        this.stopCamera();
                        await this.submitToken(token);
                    }
                } catch (error) {
                    // Coba frame berikutnya.
                }
            },

            stopCamera() {
                if (this.cameraTimer) {
                    clearInterval(this.cameraTimer);
                    this.cameraTimer = null;
                }
                if (this.stream) {
                    this.stream.getTracks().forEach((track) => track.stop());
                    this.stream = null;
                }
                this.cameraActive = false;
            },

            resultClass(result) {
                const map = {
                    valid: 'border-emerald-200 bg-emerald-50 text-emerald-800',
                    wrong_do: 'border-rose-200 bg-rose-50 text-rose-800',
                    duplicate: 'border-amber-200 bg-amber-50 text-amber-800',
                    unknown: 'border-slate-200 bg-slate-50 text-slate-700',
                    invalid_order: 'border-rose-200 bg-rose-50 text-rose-800',
                };
                return map[result] ?? map.unknown;
            },

            badgeClass(result) {
                const map = {
                    valid: 'bg-emerald-100 text-emerald-800 ring-emerald-200',
                    wrong_do: 'bg-rose-100 text-rose-800 ring-rose-200',
                    duplicate: 'bg-amber-100 text-amber-800 ring-amber-200',
                    unknown: 'bg-slate-100 text-slate-700 ring-slate-200',
                    invalid_order: 'bg-rose-100 text-rose-800 ring-rose-200',
                };
                return map[result] ?? map.unknown;
            }
         }"
         x-on:beforeunload.window="stopCamera()">

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Scanner muatan</h1>
                    <x-status-badge :status="$order->status" />
                </div>
                <p class="mt-1 text-sm text-slate-500">
                    <span class="font-mono font-medium text-slate-700">{{ $order->nomor_do }}</span>
                    &middot; {{ $order->customer->nama_pabrik }} &middot; {{ $order->fleet->nomor_polisi }}
                </p>
            </div>
            <x-button href="{{ route('warehouse.scans.index') }}" variant="ghost" icon="arrow-left">Daftar DO</x-button>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1.25fr_1fr]">
            <div class="space-y-6">
                <x-card title="Progress muatan">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-500">Rol tervalidasi</span>
                        <span class="font-semibold tabular-nums text-slate-900" x-text="loadedCount + ' / ' + totalRol + ' rol'"></span>
                    </div>
                    <div class="mt-3 h-3 w-full overflow-hidden rounded-full bg-slate-100">
                        <div class="h-3 rounded-full bg-brand-600 transition-all duration-500"
                             x-bind:style="'width:' + (totalRol > 0 ? Math.round((loadedCount / totalRol) * 100) : 0) + '%'"></div>
                    </div>
                    <p class="mt-2 text-right text-xs tabular-nums text-slate-500"
                       x-text="totalRol > 0 ? Math.round((loadedCount / totalRol) * 100) + '%' : '0%'"></p>

                    <template x-if="loadedCount >= totalRol && totalRol > 0">
                        <x-alert tone="success" title="Semua gulungan sudah dimuat" class="mt-4">
                            Muatan lengkap. Driver dapat memulai perjalanan.
                        </x-alert>
                    </template>
                </x-card>

                <x-card title="Pindai QR code" description="Gunakan kamera, scanner QR USB, atau input manual.">
                    <form x-on:submit.prevent="submitToken($refs.tokenInput.value)" class="flex flex-col gap-3 sm:flex-row">
                        <x-input x-ref="tokenInput" type="text" placeholder="Token QR akan terisi otomatis..." autofocus class="flex-1 font-mono" aria-label="Token QR" />
                        <x-button type="submit" x-bind:disabled="loading" icon="scan">
                            <span x-show="!loading">Validasi</span>
                            <span x-show="loading" x-cloak>Memproses...</span>
                        </x-button>
                    </form>

                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <x-button type="button" variant="secondary" size="sm" icon="camera" x-show="!cameraActive" x-on:click="startCamera()">Aktifkan kamera</x-button>
                        <x-button type="button" variant="danger" size="sm" icon="x" x-show="cameraActive" x-cloak x-on:click="stopCamera()">Matikan kamera</x-button>
                        <span class="text-xs text-slate-500">Kamera aktif di HTTPS atau localhost.</span>
                    </div>

                    <template x-if="cameraError">
                        <p class="mt-3 text-sm text-rose-700" x-text="cameraError"></p>
                    </template>

                    <div x-show="cameraActive" x-cloak class="mt-4">
                        <video x-ref="camera" class="w-full max-w-md rounded-xl border border-slate-200 bg-black" playsinline muted></video>
                    </div>

                    <template x-if="lastResult">
                        <div class="mt-5 rounded-xl border px-4 py-3" x-bind:class="resultClass(lastResult.result)">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold" x-text="lastResult.message"></p>
                                    <template x-if="lastResult.roll">
                                        <p class="mt-1 truncate font-mono text-xs" x-text="lastResult.roll.kode_rol + ' · Lot ' + lastResult.roll.nomor_lot + ' · ' + lastResult.roll.denier + 'D/' + lastResult.roll.filamen + 'F · ' + lastResult.roll.berat_gram + ' g'"></p>
                                    </template>
                                </div>
                                <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset" x-bind:class="badgeClass(lastResult.result)" x-text="lastResult.result_label"></span>
                            </div>
                        </div>
                    </template>
                </x-card>
            </div>

            <div class="space-y-6">
                <x-card title="Info Surat Jalan">
                    <dl class="space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-3"><dt class="text-slate-500">Status</dt><dd><x-status-badge :status="$order->status" /></dd></div>
                        <div class="flex items-center justify-between gap-3"><dt class="text-slate-500">Tanggal</dt><dd class="font-medium text-slate-900">{{ $order->tanggal->translatedFormat('d M Y') }}</dd></div>
                        <div class="flex items-center justify-between gap-3"><dt class="text-slate-500">Driver</dt><dd class="font-medium text-slate-900">{{ $order->driver->nama }}</dd></div>
                        <div class="flex items-center justify-between gap-3"><dt class="text-slate-500">Armada</dt><dd class="font-mono font-medium text-slate-900">{{ $order->fleet->nomor_polisi }}</dd></div>
                        <div class="flex items-center justify-between gap-3"><dt class="text-slate-500">Total rol</dt><dd class="font-medium tabular-nums text-slate-900">{{ $order->items->count() }}</dd></div>
                    </dl>
                </x-card>

                <x-card padding="p-0" title="Riwayat sesi ini">
                    <template x-if="history.length === 0">
                        <p class="px-5 py-8 text-center text-sm text-slate-500">Belum ada scan pada sesi ini.</p>
                    </template>

                    <ul class="divide-y divide-slate-100" x-show="history.length > 0" x-cloak>
                        <template x-for="(scan, idx) in history" x-bind:key="idx">
                            <li class="flex items-start justify-between gap-3 px-5 py-3">
                                <div class="min-w-0">
                                    <p class="truncate font-mono text-xs font-semibold text-slate-800" x-text="scan.roll"></p>
                                    <p class="text-xs text-slate-500"><span x-text="scan.time"></span> · Lot <span x-text="scan.lot"></span></p>
                                </div>
                                <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-medium ring-1 ring-inset" x-bind:class="badgeClass(scan.result)" x-text="scan.result_label"></span>
                            </li>
                        </template>
                    </ul>
                </x-card>
            </div>
        </div>

        <x-card padding="p-0" title="Checklist gulungan DO">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50/70">
                        <tr>
                            <x-th>Status</x-th>
                            <x-th>Kode rol</x-th>
                            <x-th>Nomor lot</x-th>
                            <x-th>Spesifikasi</x-th>
                            <x-th align="right">Berat (g)</x-th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($order->items as $item)
                            @php $loaded = $item->dtyRoll->status === \App\Enums\RollStatus::Loaded; @endphp
                            <tr class="{{ $loaded ? 'bg-emerald-50/50' : '' }}">
                                <x-td>
                                    @if ($loaded)
                                        <x-badge tone="success" dot>Dimuat</x-badge>
                                    @else
                                        <x-badge tone="neutral" dot>Menunggu</x-badge>
                                    @endif
                                </x-td>
                                <x-td><span class="font-mono text-xs font-semibold text-slate-800">{{ $item->dtyRoll->kode_rol }}</span></x-td>
                                <x-td>{{ $item->dtyRoll->nomor_lot }}</x-td>
                                <x-td class="tabular-nums">{{ $item->dtyRoll->denier }}D / {{ $item->dtyRoll->filamen }}F</x-td>
                                <x-td align="right" class="tabular-nums">{{ number_format((float) $item->dtyRoll->berat_gram, 2) }}</x-td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
@endsection
