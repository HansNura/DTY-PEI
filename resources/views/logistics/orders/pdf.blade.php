<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Surat Jalan {{ $order->nomor_do }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 11px; }
        h1 { margin: 0; font-size: 22px; }
        h2 { margin: 24px 0 8px; font-size: 14px; }
        .muted { color: #475569; }
        .header { border-bottom: 2px solid #1e3a8a; padding-bottom: 14px; }
        .header p { margin: 4px 0; }
        .details { width: 100%; margin-top: 16px; }
        .details td { padding: 4px 6px 4px 0; vertical-align: top; }
        .details .label { width: 110px; color: #475569; }
        table.items { width: 100%; border-collapse: collapse; }
        .items th, .items td { border: 1px solid #cbd5e1; padding: 7px; text-align: left; }
        .items th { background: #f1f5f9; }
        .signatures { width: 100%; margin-top: 54px; }
        .signatures td { width: 50%; text-align: center; vertical-align: bottom; }
        .signature-image { display: block; height: 54px; margin: 0 auto 4px; max-width: 190px; }
        .line { display: block; width: 190px; margin: 58px auto 6px; border-top: 1px solid #64748b; }
    </style>
</head>
<body>
    <div class="header">
        <h1>PMT — SURAT JALAN</h1>
        <p class="muted">Dokumen pengiriman produk DTY</p>
    </div>
    <table class="details">
        <tr><td class="label">Nomor DO</td><td><strong>{{ $order->nomor_do }}</strong></td><td class="label">Tanggal</td><td>{{ $order->tanggal->format('d/m/Y') }}</td></tr>
        <tr><td class="label">Pelanggan</td><td>{{ $order->customer->nama_pabrik }}</td><td class="label">Status</td><td>{{ $order->status->label() }}</td></tr>
        <tr><td class="label">Alamat</td><td colspan="3">{{ $order->customer->alamat }}</td></tr>
        <tr><td class="label">Kontak penerima</td><td>{{ $order->customer->kontak_nama }} · {{ $order->customer->kontak_telepon }}</td><td class="label">Armada</td><td>{{ $order->fleet->nomor_polisi }} — {{ $order->fleet->jenis_kendaraan }}</td></tr>
        <tr><td class="label">Pengemudi</td><td>{{ $order->driver->nama }}</td><td class="label">Catatan</td><td>{{ $order->catatan ?: '—' }}</td></tr>
    </table>
    <h2>Rincian barang</h2>
    <table class="items">
        <thead><tr><th>No.</th><th>Kode rol</th><th>Nomor lot</th><th>Denier</th><th>Filamen</th><th>Berat (kg)</th></tr></thead>
        <tbody>
            @foreach ($order->items as $index => $item)
                <tr><td>{{ $index + 1 }}</td><td>{{ $item->roll->kode_rol }}</td><td>{{ $item->roll->nomor_lot }}</td><td>{{ $item->roll->denier }}</td><td>{{ $item->roll->filamen }}</td><td>{{ str_replace('.', ',', $item->berat_kg) }}</td></tr>
            @endforeach
        </tbody>
        <tfoot><tr><th colspan="5">Total ({{ $order->items->count() }} rol)</th><th>{{ str_replace('.', ',', $order->totalWeightInKg()) }}</th></tr></tfoot>
    </table>
    <table class="signatures">
        <tr>
            <td>Pengemudi<span class="line"></span>{{ $order->driver->nama }}</td>
            <td>
                @if ($signatureDataUri)
                    <img class="signature-image" src="{{ $signatureDataUri }}" alt="Tanda tangan penerima">
                    Diterima oleh {{ $order->proofOfDelivery->received_by_name }}<br>
                    {{ $order->proofOfDelivery->received_at->format('d/m/Y H:i') }}
                @else
                    <span class="line"></span>{{ $order->customer->kontak_nama }}
                @endif
            </td>
        </tr>
    </table>
</body>
</html>
