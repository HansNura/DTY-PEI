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
                <h1 class="font-mono text-2xl font-semibold tracking-tight text-slate-900">{{ $order->nomor_do }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $order->customer->nama_pabrik }} &middot; {{ $order->fleet->nomor_polisi }}</p>
            </div>
            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium ring-1 ring-inset {{ $order->status->badgeClass() }}">
                {{ $order->status->label() }}
            </span>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between text-sm">
                <span class="font-medium text-slate-700">Progress muatan</span>
                <span class="font-semibold tabular-nums text-slate-900">{{ $order->loaded_count }} / {{ $order->total_rol }} rol</span>
            </div>
            <div class="mt-3 h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
                <div class="h-2.5 rounded-full bg-indigo-600"
                     style="width: {{ $order->total_rol > 0 ? round(($order->loaded_count / $order->total_rol) * 100) : 0 }}%"></div>
            </div>
            @if ($order->loaded_count < $order->total_rol)
                <p class="mt-3 text-xs text-amber-700">Armada belum dapat berangkat sebelum semua rol divalidasi checker.</p>
            @endif
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">Perbarui status perjalanan</h2>
            <p class="mt-1 text-sm text-slate-500">Koordinat GPS akan diambil otomatis bila perangkat mengizinkan.</p>

            @if (count($actions) > 0)
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach ($actions as $action)
                        <button type="button" x-on:click="submitStatus('{{ $action->value }}')" x-bind:disabled="submitting"
                                class="rounded-xl px-4 py-3 text-left text-sm font-medium text-white transition disabled:opacity-50 {{ $action === \App\Enums\TrackingStatus::Arrived ? 'bg-emerald-700 hover:bg-emerald-800' : 'bg-indigo-700 hover:bg-indigo-800' }}">
                            <span class="block">{{ $action->label() }}</span>
                            <span class="mt-0.5 block text-xs font-normal text-white/80">{{ $action->description() }}</span>
                        </button>
                    @endforeach
                </div>
            @else
                <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
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
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">Riwayat perjalanan</h2>

            <ol class="mt-4 space-y-4">
                @forelse ($order->trackingEvents as $event)
                    <li class="flex gap-3">
                        <span class="mt-1 size-2.5 shrink-0 rounded-full {{ $event->status === \App\Enums\TrackingStatus::Arrived ? 'bg-emerald-500' : 'bg-indigo-500' }}"></span>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $event->status->badgeClass() }}">
                                    {{ $event->status->label() }}
                                </span>
                                <span class="text-xs text-slate-500">{{ $event->recorded_at->translatedFormat('d M Y, H:i') }}</span>
                            </div>
                            @if ($event->hasCoordinates())
                                <a href="https://www.openstreetmap.org/?mlat={{ $event->latitude }}&mlon={{ $event->longitude }}#map=15/{{ $event->latitude }}/{{ $event->longitude }}"
                                   target="_blank" rel="noopener"
                                   class="mt-1 block text-xs text-indigo-700 hover:text-indigo-900">
                                    {{ number_format($event->latitude, 5) }}, {{ number_format($event->longitude, 5) }}
                                </a>
                            @else
                                <p class="mt-1 text-xs text-slate-500">Tanpa koordinat</p>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="text-sm text-slate-500">Belum ada riwayat perjalanan.</li>
                @endforelse
            </ol>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">Ringkasan Surat Jalan</h2>
            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between gap-3"><dt class="text-slate-500">Tujuan</dt><dd class="font-medium text-slate-900">{{ $order->customer->nama_pabrik }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-slate-500">Alamat</dt><dd class="text-right font-medium text-slate-900">{{ $order->customer->alamat }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-slate-500">Kontak</dt><dd class="font-medium text-slate-900">{{ $order->customer->kontak_nama }} &middot; {{ $order->customer->kontak_telepon }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-slate-500">Armada</dt><dd class="font-medium text-slate-900">{{ $order->fleet->nomor_polisi }} ({{ $order->fleet->jenis_kendaraan }})</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-slate-500">Tanggal</dt><dd class="font-medium text-slate-900">{{ $order->tanggal->translatedFormat('d M Y') }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-slate-500">Total berat</dt><dd class="font-medium text-slate-900">{{ number_format($order->total_berat_kg, 2) }} kg</dd></div>
            </dl>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="text-sm font-semibold text-slate-900">Daftar rol</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">Kode</th>
                            <th class="px-5 py-3 font-medium">Lot</th>
                            <th class="px-5 py-3 font-medium">Spesifikasi</th>
                            <th class="px-5 py-3 text-right font-medium">Berat (kg)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($order->items as $item)
                            <tr>
                                <td class="px-5 py-3 font-mono text-xs font-semibold text-slate-800">{{ $item->dtyRoll->kode_rol }}</td>
                                <td class="px-5 py-3 text-slate-700">{{ $item->dtyRoll->nomor_lot }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $item->dtyRoll->denier }}D / {{ $item->dtyRoll->filamen }}F</td>
                                <td class="px-5 py-3 text-right tabular-nums text-slate-700">{{ number_format($item->berat_kg, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <a href="{{ route('driver.deliveries.index') }}" class="inline-flex text-sm font-medium text-slate-600 hover:text-slate-900">Kembali ke daftar perjalanan</a>
    </div>
@endsection
