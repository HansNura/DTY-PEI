<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak DO — {{ $order->nomor_do }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #1e293b; padding: 20mm 15mm; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #1e293b; padding-bottom: 12px; margin-bottom: 16px; }
        .company-name { font-size: 20px; font-weight: bold; }
        .doc-title { font-size: 16px; font-weight: bold; text-align: right; }
        .doc-number { font-size: 13px; color: #475569; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
        .info-box { border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px 12px; }
        .info-label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; margin-bottom: 4px; }
        .info-value { font-size: 12px; font-weight: bold; }
        .info-sub { font-size: 11px; color: #475569; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th { background: #f1f5f9; font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; font-weight: bold; padding: 6px 8px; border: 1px solid #e2e8f0; text-align: left; }
        td { padding: 6px 8px; border: 1px solid #e2e8f0; }
        tfoot td { background: #f8fafc; font-weight: bold; }
        .signature-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-top: 32px; }
        .signature-box { border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px; text-align: center; }
        .signature-label { font-size: 10px; margin-bottom: 50px; }
        .signature-line { border-top: 1px solid #1e293b; padding-top: 4px; font-size: 10px; color: #475569; }
        @media print { @page { margin: 10mm; } }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="company-name">PMT — Pelacakan DTY</div>
            <div class="info-sub">Sistem Pelacakan Rol Benang</div>
        </div>
        <div style="text-align:right">
            <div class="doc-title">SURAT JALAN</div>
            <div class="doc-number">{{ $order->nomor_do }}</div>
            <div class="info-sub">Tanggal: {{ $order->tanggal->format('d M Y') }}</div>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-box">
            <div class="info-label">Pelanggan Tujuan</div>
            <div class="info-value">{{ $order->customer->nama_pabrik }}</div>
            <div class="info-sub">{{ $order->customer->alamat }}</div>
            <div class="info-sub">Penerima: {{ $order->customer->kontak_nama }} · {{ $order->customer->kontak_telepon }}</div>
        </div>
        <div class="info-box">
            <div class="info-label">Armada &amp; Driver</div>
            <div class="info-value">{{ $order->fleet->nomor_polisi }}</div>
            <div class="info-sub">{{ $order->fleet->jenis_kendaraan }}</div>
            <div class="info-sub">Driver: {{ $order->driver->nama }}</div>
        </div>
    </div>

    @if ($order->catatan)
        <p style="margin-bottom:12px; padding:8px 12px; background:#fefce8; border:1px solid #fde68a; border-radius:4px; font-size:11px;">
            <strong>Catatan:</strong> {{ $order->catatan }}
        </p>
    @endif

    <table>
        <thead>
            <tr>
                <th style="width:30px">No.</th>
                <th>Kode Rol</th>
                <th>Nomor Lot</th>
                <th>Spesifikasi</th>
                <th style="text-align:right">Berat (kg)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td style="font-family:monospace; font-size:11px">{{ $item->dtyRoll->kode_rol }}</td>
                    <td>{{ $item->dtyRoll->nomor_lot }}</td>
                    <td>{{ $item->dtyRoll->denier }}D / {{ $item->dtyRoll->filamen }}F</td>
                    <td style="text-align:right">{{ number_format($item->berat_kg, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" style="text-align:right">Total ({{ $order->items->count() }} rol)</td>
                <td style="text-align:right">{{ number_format($order->total_berat_kg, 2) }} kg</td>
            </tr>
        </tfoot>
    </table>

    <div class="signature-grid">
        <div class="signature-box">
            <div class="signature-label">Disiapkan oleh</div>
            <div class="signature-line">Admin Gudang</div>
        </div>
        <div class="signature-box">
            <div class="signature-label">Driver</div>
            <div class="signature-line">{{ $order->driver->nama }}</div>
        </div>
        <div class="signature-box">
            <div class="signature-label">Diterima oleh</div>
            <div class="signature-line">{{ $order->customer->kontak_nama }}</div>
        </div>
    </div>

    <script>window.onload = function() { window.print(); }</script>
</body>
</html>
