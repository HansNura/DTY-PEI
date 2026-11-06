@extends('layouts.app')

@section('title', 'Perjalanan — ' . $order->nomor_do)

@section('content')
    @php
        $cartonRows = $order->items
            ->filter(fn ($item) => $item->dtyRoll?->carton !== null)
            ->groupBy(fn ($item) => $item->dtyRoll->carton->id)
            ->map(function ($items): array {
                $loaded = $items->filter(fn ($item) => in_array($item->dtyRoll->status, [\App\Enums\RollStatus::Loaded, \App\Enums\RollStatus::Delivered], true))->count();

                return ['loaded' => $loaded, 'total' => $items->count()];
            })
            ->values();
        $totalBox = $cartonRows->count();
        $loadedBoxCount = $cartonRows->filter(fn (array $row): bool => $row['loaded'] === $row['total'])->count();

        $palletGroups = $order->palletGroups();
        $palletByCarton = collect($palletGroups)->reduce(function (array $carry, array $pallet): array {
            foreach ($pallet['cartons'] as $box) {
                $carry[$box['carton']->id] = $pallet['number'];
            }

            return $carry;
        }, []);
        $receiptBoxes = $order->items
            ->filter(fn ($item) => $item->dtyRoll?->carton !== null)
            ->groupBy(fn ($item) => $item->dtyRoll->carton->id)
            ->map(function ($items) use ($palletByCarton): array {
                $carton = $items->first()->dtyRoll->carton;

                return [
                    'carton' => $carton,
                    'pallet' => $palletByCarton[$carton->id] ?? null,
                    'bobbin_count' => $items->count(),
                    'net_kg' => round($items->sum(fn ($item): float => (float) $item->berat_kg), 2),
                ];
            })
            ->sortBy(fn (array $box): string => $box['carton']->nomor_hu)
            ->values();
        $receiptScanCount = $receiptBoxes->isNotEmpty() ? $receiptBoxes->count() : $order->items->count();
    @endphp

    <div class="mx-auto max-w-3xl space-y-6"
         x-data="{
            submitting: false,
            status: '',
            latitude: @js(old('latitude', '')),
            longitude: @js(old('longitude', '')),
            locationSource: '',
            locationMessage: null,

            async submitStatus(nextStatus) {
                if (this.submitting) return;
                this.submitting = true;
                this.status = nextStatus;
                this.locationMessage = null;

                try {
                    if ((this.latitude === '' || this.longitude === '') && navigator.geolocation) {
                        const position = await new Promise((resolve, reject) => {
                            navigator.geolocation.getCurrentPosition(resolve, reject, {
                                enableHighAccuracy: true,
                                timeout: 8000,
                                maximumAge: 30000,
                            });
                        });

                        this.latitude = position.coords.latitude.toFixed(7);
                        this.longitude = position.coords.longitude.toFixed(7);
                        this.locationSource = 'GPS';
                    }
                } catch (error) {
                    this.locationMessage = 'Lokasi tidak tersedia. Status tetap dikirim tanpa koordinat.';
                }

                const form = this.$refs.statusForm;
                form.querySelector('[name=status]').value = this.status;
                form.querySelector('[name=latitude]').value = this.latitude;
                form.querySelector('[name=longitude]').value = this.longitude;
                form.submit();
            }
         }"
         x-on:trip-location-selected.window="
            latitude = $event.detail.latitude;
            longitude = $event.detail.longitude;
            locationSource = $event.detail.source;
            locationMessage = null;
         ">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Perjalanan</p>
                <h1 class="mt-1 font-mono text-2xl font-semibold tracking-tight text-slate-900">{{ $order->nomor_do }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $order->customer->nama_pabrik }} &middot; {{ $order->fleet->nomor_polisi }}</p>
            </div>
            <div class="flex flex-col items-end gap-2">
                <x-status-badge :status="$order->status" />
                <x-button href="{{ route('driver.deliveries.index') }}" variant="ghost" size="sm" icon="arrow-left">Kembali</x-button>
            </div>
        </div>

        <x-card>
            <x-status-stepper :status="$order->status" />

            <div class="mt-5 flex flex-wrap items-center justify-between gap-2 text-sm">
                <span class="font-medium text-slate-700">Progress muatan</span>
                <span class="font-semibold tabular-nums text-slate-900">
                    @if ($totalBox > 0)
                        {{ $loadedBoxCount }} / {{ $totalBox }} kardus
                        <span class="font-normal text-slate-500">· {{ $order->loaded_count }} / {{ $order->total_rol }} bobbin</span>
                    @else
                        {{ $order->loaded_count }} / {{ $order->total_rol }} bobbin
                    @endif
                </span>
            </div>
            @if ($totalBox > 0)
                <x-progress :value="$loadedBoxCount" :max="$totalBox" class="mt-2" />
            @else
                <x-progress :value="$order->loaded_count" :max="$order->total_rol" class="mt-2" />
            @endif

            @if (($totalBox > 0 && $loadedBoxCount < $totalBox) || ($totalBox === 0 && $order->loaded_count < $order->total_rol))
                <x-alert tone="warning" class="mt-4">
                    Armada belum dapat berangkat sebelum semua kardus/bobbin divalidasi checker.
                </x-alert>
            @endif
        </x-card>

        @if ($order->proofOfDelivery)
            <x-card title="Penerimaan dikonfirmasi">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-slate-900">{{ $order->proofOfDelivery->received_by_name }}</p>
                        <p class="mt-1 text-xs text-slate-500">Diterima {{ $order->proofOfDelivery->received_at->translatedFormat('d M Y, H:i') }}</p>
                    </div>
                    <x-badge tone="success" dot>POD tersimpan</x-badge>
                </div>
                <img src="{{ route('deliveries.signature', $order) }}" alt="Tanda tangan penerima {{ $order->proofOfDelivery->received_by_name }}"
                     class="mt-4 max-h-32 rounded-xl border border-slate-200 bg-white p-2">
            </x-card>
        @endif

        <x-card title="Perbarui status perjalanan" description="Pilih titik lokasi di peta, lalu tekan status yang sesuai. Koordinat dikirim bersama pembaruan status.">
            @if (count($actions) > 0)
                <div class="overflow-hidden rounded-xl border border-slate-200">
                    <div id="driver-trip-map"
                         class="map-embed h-72 w-full bg-slate-100"
                         data-initial-lat="{{ old('latitude') }}"
                         data-initial-lng="{{ old('longitude') }}"
                         data-last-lat="{{ $order->latestTrackingEvent?->latitude }}"
                         data-last-lng="{{ $order->latestTrackingEvent?->longitude }}"
                         data-destination-lat="{{ $order->customer->latitude }}"
                         data-destination-lng="{{ $order->customer->longitude }}"
                         data-destination-label="{{ $order->customer->nama_pabrik }}"
                         aria-label="Peta pemilihan lokasi perjalanan"></div>
                </div>

                <div class="mt-3 flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-800">Lokasi yang akan dikirim</p>
                        <p class="mt-0.5 font-mono text-xs text-slate-700" x-text="latitude && longitude ? latitude + ', ' + longitude : 'Belum ada titik dipilih'"></p>
                        <p class="mt-0.5 text-[10px] text-slate-500" x-show="locationSource" x-text="'Sumber: ' + locationSource"></p>
                        <p class="mt-1 text-xs text-slate-500" data-trip-map-status aria-live="polite">Klik peta untuk memilih titik, atau gunakan lokasi saat ini.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" data-trip-use-gps
                                class="inline-flex items-center gap-2 rounded-xl border border-brand-200 bg-brand-50 px-3.5 py-2 text-xs font-medium text-brand-700 transition hover:bg-brand-100">
                            <x-icon name="map-pin" class="size-4" />
                            Gunakan lokasi saat ini
                        </button>
                        <button type="button" data-trip-clear
                                class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-medium text-slate-500 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700">
                            <x-icon name="x" class="size-4" />
                            Hapus titik
                        </button>
                    </div>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    @foreach ($actions as $action)
                        @php
                            $isPositionUpdate = $order->status === \App\Enums\DeliveryOrderStatus::InTransit && $action === \App\Enums\TrackingStatus::InTransit;
                            $isStartTrip = $order->status === \App\Enums\DeliveryOrderStatus::Departed && $action === \App\Enums\TrackingStatus::InTransit;
                        @endphp
                        <button type="button" x-on:click="submitStatus('{{ $action->value }}')" x-bind:disabled="submitting"
                                class="rounded-xl px-4 py-4 text-left text-sm font-semibold text-white transition disabled:opacity-50 {{ $action === \App\Enums\TrackingStatus::Arrived ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-brand-600 hover:bg-brand-700' }}">
                            <span class="flex items-center gap-2">
                                <x-icon :name="$action === \App\Enums\TrackingStatus::Arrived ? 'map-pin' : 'truck'" class="size-4" />
                                @if ($isPositionUpdate)
                                    Perbarui posisi
                                @elseif ($isStartTrip)
                                    Mulai perjalanan
                                @else
                                    {{ $action->label() }}
                                @endif
                            </span>
                            <span class="mt-1 block text-xs font-normal text-white/80">
                                @if ($isPositionUpdate)
                                    Kirim titik lokasi terbaru armada.
                                @elseif ($isStartTrip)
                                    Armada meninggalkan gudang menuju pelanggan.
                                @else
                                    {{ $action->description() }}
                                @endif
                            </span>
                        </button>
                    @endforeach
                </div>
            @else
                <div class="rounded-xl bg-slate-50 px-4 py-4 text-sm text-slate-600">
                    @if (in_array($order->status, [\App\Enums\DeliveryOrderStatus::Cancelled, \App\Enums\DeliveryOrderStatus::Received], true))
                        Surat Jalan ini sudah final dan tidak dapat diperbarui.
                    @elseif (in_array($order->status, [\App\Enums\DeliveryOrderStatus::Issued, \App\Enums\DeliveryOrderStatus::Loading], true))
                        Menunggu semua kardus/bobbin divalidasi oleh checker sebelum perjalanan dapat dimulai.
                    @elseif ($order->status === \App\Enums\DeliveryOrderStatus::Arrived && ! $order->proofOfDelivery)
                        Armada sudah tiba. Lanjutkan konfirmasi penerimaan pelanggan di bawah.
                    @else
                        Tidak ada aksi status yang tersedia saat ini.
                    @endif

                    @if ($order->status === \App\Enums\DeliveryOrderStatus::Arrived && ! $order->proofOfDelivery)
                        <x-button href="#konfirmasi-penerimaan" class="mt-3" icon="check">Konfirmasi penerimaan</x-button>
                    @endif
                </div>
            @endif

            <template x-if="locationMessage">
                <p class="mt-3 text-sm text-amber-700" x-text="locationMessage"></p>
            </template>

            <form method="POST" action="{{ route('driver.deliveries.status', $order) }}" x-ref="statusForm" class="hidden">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" x-bind:value="status">
                <input type="hidden" id="trip-latitude" name="latitude" x-bind:value="latitude">
                <input type="hidden" id="trip-longitude" name="longitude" x-bind:value="longitude">
            </form>
        </x-card>

        @if ($order->status === \App\Enums\DeliveryOrderStatus::Arrived && ! $order->proofOfDelivery)
            <x-card id="konfirmasi-penerimaan" title="Konfirmasi penerimaan pelanggan" description="Pelanggan memeriksa dan menandatangani di perangkat ini. Pindai QR setiap kardus sesuai surat jalan.">
                <form method="POST" action="{{ route('driver.deliveries.confirm', $order) }}" class="space-y-5" data-receipt-form>
                    @csrf

                    <x-field label="Nama penerima" name="received_by_name" required>
                        <x-input id="received_by_name" name="received_by_name" type="text" required maxlength="100"
                                 value="{{ old('received_by_name', $order->customer->kontak_nama) }}" />
                    </x-field>

                    <div>
                        <h3 class="text-sm font-medium text-slate-700">Scan QR / input manual kardus</h3>
                        <p class="mt-1 text-xs text-slate-500">Scan QR kardus atau ketik nomor HU. Satu scan mewakili seluruh bobbin di dalam kardus. QR tidak terdaftar, berbeda, atau berulang akan ditolak.</p>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <input type="text" data-scan-input autocomplete="off" placeholder="Scan QR / nomor HU kardus" aria-label="Kode hasil scan"
                                   class="min-w-0 flex-1 rounded-xl border border-slate-200 px-3.5 py-2.5 font-mono text-sm focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 focus:outline-none">
                            <button type="button" data-add-scan class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Tambah scan</button>
                            <button type="button" data-start-camera class="rounded-xl border border-brand-200 px-4 py-2.5 text-sm font-medium text-brand-700 hover:bg-brand-50">Buka kamera</button>
                            <button type="button" data-stop-camera class="hidden rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Tutup kamera</button>
                        </div>

                        <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50/60 p-3">
                            <p class="text-xs font-semibold text-slate-700">Input manual</p>
                            <p class="mt-0.5 text-[10px] text-slate-500">Gunakan jika QR kardus tidak terbaca.</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <input type="text" data-manual-input autocomplete="off" placeholder="Ketik nomor HU kardus" aria-label="Nomor HU kardus manual"
                                       class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 font-mono text-sm focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 focus:outline-none">
                                <button type="button" data-add-manual class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Tambah manual</button>
                            </div>
                        </div>

                        <video data-scan-video class="mt-3 hidden max-h-64 w-full rounded-xl bg-slate-900 object-contain" playsinline aria-label="Pratinjau kamera scan QR"></video>
                        <p data-scan-message class="mt-2 text-sm text-slate-600" aria-live="polite"></p>
                        <div data-scan-list class="mt-3 flex flex-wrap gap-2"></div>

                        <div class="mt-4 overflow-hidden rounded-xl border border-slate-200">
                            <div class="flex items-center justify-between gap-2 bg-slate-50 px-3 py-2">
                                <p class="text-xs font-semibold text-slate-700">{{ $receiptBoxes->isNotEmpty() ? 'Daftar kardus yang harus discan' : 'Daftar bobbin yang harus discan' }}</p>
                                <span data-scan-progress class="rounded-full bg-white px-2 py-0.5 text-[10px] font-semibold tabular-nums text-slate-600 ring-1 ring-inset ring-slate-200">0/{{ $receiptScanCount }}</span>
                            </div>
                            <ul class="max-h-64 divide-y divide-slate-100 overflow-y-auto">
                                @if ($receiptBoxes->isNotEmpty())
                                    @foreach ($receiptBoxes as $box)
                                        <li class="flex items-center justify-between gap-3 px-3 py-2.5"
                                            data-scan-item
                                            data-scan-hu="{{ $box['carton']->nomor_hu }}"
                                            data-scan-token="{{ $box['carton']->qr_token }}"
                                            data-scan-serial=""
                                            data-scanned="0">
                                            <div class="min-w-0">
                                                <p class="font-mono text-[11px] font-semibold text-slate-800">{{ $box['carton']->nomor_hu }}</p>
                                                <p class="mt-0.5 text-[10px] text-slate-500">
                                                    @if ($box['pallet'])
                                                        <span class="mr-1 rounded bg-slate-100 px-1.5 py-0.5 font-semibold text-slate-600">{{ $box['pallet'] }}</span>
                                                    @endif
                                                    Merge {{ $box['carton']->nomor_merge }} · {{ $box['bobbin_count'] }} bobbin · {{ number_format($box['net_kg'], 2, ',', '.') }} kg
                                                </p>
                                            </div>
                                            <span data-scan-status class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-500">Belum</span>
                                        </li>
                                    @endforeach
                                @else
                                    @foreach ($order->items as $item)
                                        <li class="flex items-center justify-between gap-3 px-3 py-2.5"
                                            data-scan-item
                                            data-scan-hu=""
                                            data-scan-serial="{{ $item->dtyRoll?->nomor_seri }}"
                                            data-scan-token="{{ $item->dtyRoll?->qr_token }}"
                                            data-scanned="0">
                                            <div class="min-w-0">
                                                <p class="font-mono text-[11px] font-semibold text-slate-800">{{ $item->dtyRoll?->nomor_seri ?? '—' }}</p>
                                                <p class="mt-0.5 text-[10px] text-slate-500">Lot {{ $item->dtyRoll?->nomor_lot }} · {{ number_format((float) $item->berat_kg, 2) }} kg</p>
                                            </div>
                                            <span data-scan-status class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-500">Belum</span>
                                        </li>
                                    @endforeach
                                @endif
                            </ul>
                        </div>

                        @foreach (old('scanned_tokens', []) as $token)
                            <input type="hidden" name="scanned_tokens[]" value="{{ $token }}" data-scanned-token>
                        @endforeach
                        @error('scanned_tokens')
                            <p class="mt-2 text-sm text-rose-700">{{ $message }}</p>
                        @enderror
                        @error('scanned_tokens.*')
                            <p class="mt-2 text-sm text-rose-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <h3 class="text-sm font-medium text-slate-700">Tanda tangan penerima</h3>
                        <p class="mt-1 text-xs text-slate-500">Minta pelanggan membubuhkan tanda tangan dengan jari pada area berikut.</p>
                        <canvas data-signature-pad class="mt-3 h-44 w-full touch-none rounded-xl border border-slate-200 bg-white" aria-label="Area tanda tangan penerima"></canvas>
                        <input type="hidden" name="signature" data-signature-input>
                        <button type="button" data-clear-signature class="mt-2 text-sm font-medium text-brand-700 hover:text-brand-900">Hapus tanda tangan</button>
                        @error('signature')
                            <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-button type="submit" class="w-full sm:w-auto" icon="check">Simpan konfirmasi penerimaan</x-button>
                </form>
            </x-card>
        @endif

        <x-card title="Riwayat perjalanan">
            <x-timeline>
                @forelse ($order->trackingEvents as $event)
                    <x-timeline-item
                        :title="$event->status->label()"
                        :meta="$event->recorded_at->translatedFormat('d M Y, H:i')"
                        :tone="\App\Support\StatusTone::for($event->status)"
                    >
                        @if ($event->hasCoordinates())
                            <a href="https://www.openstreetmap.org/?mlat={{ $event->latitude }}&mlon={{ $event->longitude }}#map=15/{{ $event->latitude }}/{{ $event->longitude }}"
                               target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-xs text-brand-700">
                                <x-icon name="map-pin" class="size-3.5" />
                                {{ number_format($event->latitude, 5) }}, {{ number_format($event->longitude, 5) }}
                            </a>
                        @else
                            <span class="text-xs text-slate-500">Tanpa koordinat</span>
                        @endif
                    </x-timeline-item>
                @empty
                    <li class="text-sm text-slate-500">Belum ada riwayat perjalanan.</li>
                @endforelse
            </x-timeline>
        </x-card>

        <div class="grid gap-6 sm:grid-cols-2">
            <x-card title="Tujuan">
                <p class="font-semibold text-slate-900">{{ $order->customer->nama_pabrik }}</p>
                <p class="mt-1 text-sm text-slate-600">{{ $order->customer->alamat }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ $order->customer->kontak_nama }} · {{ $order->customer->kontak_telepon }}</p>
            </x-card>

            <x-card title="Ringkasan">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Armada</dt><dd class="font-medium text-slate-900">{{ $order->fleet->nomor_polisi }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Jenis</dt><dd class="font-medium text-slate-900">{{ $order->fleet->jenis_kendaraan }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Tanggal</dt><dd class="font-medium text-slate-900">{{ $order->tanggal->translatedFormat('d M Y') }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Total berat</dt><dd class="font-medium tabular-nums text-slate-900">{{ number_format($order->total_berat_kg, 2) }} kg</dd></div>
                </dl>
            </x-card>
        </div>

        <x-card padding="p-0" title="Daftar kardus & pallet" description="Muatan ditampilkan per kardus dan pallet, bukan per bobbin.">
            @if ($palletGroups === [])
                <div class="p-5">
                    <x-empty-state icon="boxes" title="Belum ada data kardus" description="Surat Jalan ini belum memiliki data kardus/pallet." />
                </div>
            @else
                @foreach ($palletGroups as $pallet)
                    <div class="border-b border-slate-100 last:border-b-0">
                        <div class="flex flex-wrap items-center justify-between gap-2 bg-slate-50/80 px-4 py-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-800">Pallet {{ $pallet['number'] }}</p>
                                <p class="mt-0.5 text-[11px] text-slate-500">{{ $pallet['box_count'] }}/30 kardus · {{ $pallet['bobbin_count'] }} bobbin</p>
                            </div>
                            <p class="text-sm font-semibold tabular-nums text-slate-800">{{ number_format($pallet['net_kg'], 2, ',', '.') }} kg netto</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-100 text-sm">
                                <thead class="bg-white text-[10px] uppercase tracking-wide text-slate-400">
                                    <tr>
                                        <x-th>Kardus</x-th>
                                        <x-th>Merge</x-th>
                                        <x-th>Spesifikasi</x-th>
                                        <x-th>Isi bobbin</x-th>
                                        <x-th align="right">Netto (kg)</x-th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($pallet['cartons'] as $box)
                                        <tr>
                                            <x-td><span class="font-mono text-xs font-semibold text-slate-800">{{ $box['carton']->nomor_hu }}</span></x-td>
                                            <x-td>{{ $box['carton']->nomor_merge }}</x-td>
                                            <x-td class="tabular-nums">{{ $box['carton']->specification }}</x-td>
                                            <x-td class="tabular-nums">{{ $box['bobbin_count'] }} bobbin</x-td>
                                            <x-td align="right" class="tabular-nums">{{ number_format($box['net_kg'], 2, ',', '.') }}</x-td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            @endif
        </x-card>
    </div>

    @push('scripts')
        @vite('resources/js/driver-trip-map.js')
    @endpush
@endsection
