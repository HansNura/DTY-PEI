@extends('layouts.app')

@section('title', 'Perjalanan — ' . $order->nomor_do)

@section('content')
    <div class="mx-auto max-w-3xl space-y-6"
         x-data="{
            submitting: false,
            status: '',
            latitude: '',
            longitude: '',
            locationMessage: null,

            async submitStatus(nextStatus) {
                if (this.submitting) return;
                this.submitting = true;
                this.status = nextStatus;
                this.locationMessage = null;

                try {
                    if (navigator.geolocation) {
                        const position = await new Promise((resolve, reject) => {
                            navigator.geolocation.getCurrentPosition(resolve, reject, {
                                enableHighAccuracy: true,
                                timeout: 8000,
                                maximumAge: 30000,
                            });
                        });

                        this.latitude = position.coords.latitude.toFixed(7);
                        this.longitude = position.coords.longitude.toFixed(7);
                    }
                } catch (error) {
                    this.locationMessage = 'Lokasi tidak tersedia. Status tetap dikirim tanpa koordinat.';
                }

                this.$refs.statusForm.submit();
            }
         }">
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

            <div class="mt-5 flex items-center justify-between text-sm">
                <span class="font-medium text-slate-700">Progress muatan</span>
                <span class="font-semibold tabular-nums text-slate-900">{{ $order->loaded_count }} / {{ $order->total_rol }} rol</span>
            </div>
            <x-progress :value="$order->loaded_count" :max="$order->total_rol" class="mt-2" />

            @if ($order->loaded_count < $order->total_rol)
                <x-alert tone="warning" class="mt-4">
                    Armada belum dapat berangkat sebelum semua rol divalidasi checker.
                </x-alert>
            @endif
        </x-card>

        <x-card title="Perbarui status perjalanan" description="Koordinat GPS diambil otomatis bila perangkat mengizinkan.">
            @if (count($actions) > 0)
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($actions as $action)
                        <button type="button" x-on:click="submitStatus('{{ $action->value }}')" x-bind:disabled="submitting"
                                class="rounded-xl px-4 py-4 text-left text-sm font-semibold text-white transition disabled:opacity-50 {{ $action === \App\Enums\TrackingStatus::Arrived ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-brand-600 hover:bg-brand-700' }}">
                            <span class="flex items-center gap-2">
                                <x-icon :name="$action === \App\Enums\TrackingStatus::Arrived ? 'map-pin' : 'truck'" class="size-4" />
                                {{ $action->label() }}
                            </span>
                            <span class="mt-1 block text-xs font-normal text-white/80">{{ $action->description() }}</span>
                        </button>
                    @endforeach
                </div>
            @else
                <div class="rounded-xl bg-slate-50 px-4 py-4 text-sm text-slate-600">
                    @if (in_array($order->status, [\App\Enums\DeliveryOrderStatus::Cancelled, \App\Enums\DeliveryOrderStatus::Received], true))
                        Surat Jalan ini sudah final dan tidak dapat diperbarui.
                    @elseif (in_array($order->status, [\App\Enums\DeliveryOrderStatus::Issued, \App\Enums\DeliveryOrderStatus::Loading], true))
                        Menunggu semua rol divalidasi oleh checker sebelum perjalanan dapat dimulai.
                    @else
                        Tidak ada aksi status yang tersedia saat ini.
                    @endif
                </div>
            @endif

            <template x-if="locationMessage">
                <p class="mt-3 text-sm text-amber-700" x-text="locationMessage"></p>
            </template>

            <form method="POST" action="{{ route('driver.deliveries.status', $order) }}" x-ref="statusForm" class="hidden">
                @csrf
                <input type="hidden" name="status" x-bind:value="status">
                <input type="hidden" name="latitude" x-bind:value="latitude">
                <input type="hidden" name="longitude" x-bind:value="longitude">
            </form>
        </x-card>

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

        <x-card padding="p-0" title="Daftar rol">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50/70">
                        <tr>
                            <x-th>Kode</x-th>
                            <x-th>Lot</x-th>
                            <x-th>Spesifikasi</x-th>
                            <x-th align="right">Berat (kg)</x-th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($order->items as $item)
                            <tr>
                                <x-td><span class="font-mono text-xs font-semibold text-slate-800">{{ $item->dtyRoll->kode_rol }}</span></x-td>
                                <x-td>{{ $item->dtyRoll->nomor_lot }}</x-td>
                                <x-td class="tabular-nums">{{ $item->dtyRoll->denier }}D / {{ $item->dtyRoll->filamen }}F</x-td>
                                <x-td align="right" class="tabular-nums">{{ number_format($item->berat_kg, 2) }}</x-td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
@endsection
