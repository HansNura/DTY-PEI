@extends('layouts.app')

@section('title', $order->nomor_do)

@section('content')
    <div>
        <a href="{{ route('driver.deliveries.index') }}" class="text-sm font-medium text-indigo-700 hover:text-indigo-900">Kembali ke pengiriman</a>
        <h1 class="mt-3 text-2xl font-semibold tracking-tight text-slate-900">{{ $order->nomor_do }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $order->customer->nama_pabrik }} · {{ $order->status->label() }}</p>
    </div>

    <div class="mt-6 grid gap-5 md:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-slate-900">Tujuan pengiriman</h2>
            <p class="mt-3 text-sm font-medium">{{ $order->customer->nama_pabrik }}</p>
            <p class="mt-1 text-sm text-slate-600">{{ $order->customer->alamat }}</p>
            <p class="mt-3 text-sm text-slate-600">Penerima: {{ $order->customer->kontak_nama }} · {{ $order->customer->kontak_telepon }}</p>
            <p class="mt-2 text-sm text-slate-600">Armada: {{ $order->fleet->nomor_polisi }} · {{ $order->fleet->jenis_kendaraan }}</p>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-slate-900">Rincian gulungan ({{ $order->items->count() }})</h2>
            <ul class="mt-3 divide-y divide-slate-100">
                @foreach ($order->items as $item)
                    <li class="flex justify-between gap-3 py-2 text-sm"><span>{{ $item->roll->kode_rol }} <span class="text-slate-500">· Lot {{ $item->roll->nomor_lot }}</span></span><span class="shrink-0">{{ str_replace('.', ',', $item->berat_kg) }} kg</span></li>
                @endforeach
            </ul>
        </section>
    </div>

    @if ($order->proofOfDelivery)
        <section class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
            <h2 class="font-semibold text-emerald-900">Penerimaan dikonfirmasi</h2>
            <p class="mt-1 text-sm text-emerald-800">Penerima: {{ $order->proofOfDelivery->received_by_name }} · {{ $order->proofOfDelivery->received_at->format('d/m/Y H:i') }}</p>
            <img src="{{ route('deliveries.signature', $order) }}" alt="Tanda tangan penerima {{ $order->proofOfDelivery->received_by_name }}" class="mt-3 max-h-32 rounded-lg border border-emerald-200 bg-white">
        </section>
    @elseif ($order->status->next())
        <section class="mt-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-slate-900">Perbarui status perjalanan</h2>
            <p class="mt-1 text-sm text-slate-500">Status berikutnya: {{ $order->status->next()->label() }}</p>
            <form method="POST" action="{{ route('driver.deliveries.status', $order) }}" class="mt-4 flex flex-wrap items-end gap-3">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="{{ $order->status->next()->value }}">
                <button type="submit" class="rounded-lg bg-indigo-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-indigo-800">Tandai {{ strtolower($order->status->next()->label()) }}</button>
            </form>
        </section>
    @endif

    @if ($order->status->value === 'arrived' && ! $order->proofOfDelivery)
        <section class="mt-5 rounded-2xl border border-indigo-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Konfirmasi penerimaan pelanggan</h2>
            <p class="mt-1 text-sm text-slate-500">Pelanggan memeriksa dan menandatangani di perangkat ini. Pindai QR setiap gulungan sesuai surat jalan.</p>
            <form method="POST" action="{{ route('driver.deliveries.confirm', $order) }}" class="mt-5 space-y-5" data-receipt-form>
                @csrf
                <div>
                    <label for="received_by_name" class="text-sm font-medium text-slate-700">Nama penerima</label>
                    <input id="received_by_name" name="received_by_name" type="text" required maxlength="100" value="{{ old('received_by_name', $order->customer->kontak_nama) }}" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                    @error('received_by_name')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>

                <div>
                    <h3 class="text-sm font-medium text-slate-700">Scan QR gulungan</h3>
                    <p class="mt-1 text-xs text-slate-500">Scan wajib cocok dengan seluruh rol pada DO; QR tidak terdaftar, berbeda, atau berulang akan ditolak. Kamera memerlukan HTTPS atau localhost dan izin akses; gunakan scanner eksternal atau input manual jika kamera tidak tersedia.</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <input type="text" data-scan-input autocomplete="off" placeholder="Kode hasil scan" aria-label="Kode hasil scan" class="min-w-0 flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                        <button type="button" data-add-scan class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Tambah scan</button>
                        <button type="button" data-start-camera class="rounded-lg border border-indigo-200 px-4 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-50">Buka kamera</button>
                        <button type="button" data-stop-camera class="hidden rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Tutup kamera</button>
                    </div>
                    <video data-scan-video class="mt-3 hidden max-h-64 w-full rounded-xl bg-slate-900 object-contain" playsinline aria-label="Pratinjau kamera scan QR"></video>
                    <p data-scan-message class="mt-2 text-sm text-slate-600" aria-live="polite"></p>
                    <div data-scan-list class="mt-3 flex flex-wrap gap-2"></div>
                    @foreach (old('scanned_tokens', []) as $token)
                        <input type="hidden" name="scanned_tokens[]" value="{{ $token }}" data-scanned-token>
                    @endforeach
                    @error('scanned_tokens')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                    @error('scanned_tokens.*')<p class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>

                <div>
                    <h3 class="text-sm font-medium text-slate-700">Tanda tangan penerima</h3>
                    <p class="mt-1 text-xs text-slate-500">Minta pelanggan membubuhkan tanda tangan dengan jari pada area berikut.</p>
                    <canvas data-signature-pad class="mt-3 h-44 w-full touch-none rounded-xl border border-slate-300 bg-white" aria-label="Area tanda tangan penerima"></canvas>
                    <input type="hidden" name="signature" data-signature-input>
                    <button type="button" data-clear-signature class="mt-2 text-sm font-medium text-indigo-700 hover:text-indigo-900">Hapus tanda tangan</button>
                    @error('signature')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>

                <button type="submit" class="w-full rounded-lg bg-indigo-700 px-4 py-3 text-sm font-semibold text-white transition hover:bg-indigo-800 sm:w-auto">Simpan konfirmasi penerimaan</button>
            </form>
        </section>
    @endif
@endsection
