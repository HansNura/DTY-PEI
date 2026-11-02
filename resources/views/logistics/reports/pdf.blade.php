<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap Pengiriman PMT</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 9px; }
        h1 { margin: 0 0 6px; font-size: 18px; }
        p { margin: 4px 0; color: #475569; }
        table { width: 100%; margin-top: 16px; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 6px; text-align: left; vertical-align: top; }
        th { background: #f1f5f9; }
    </style>
</head>
<body>
    <h1>PMT — Rekapitulasi Pengiriman DTY</h1>
    <p>Periode: {{ $filters['from'] ?? 'Semua' }} s.d. {{ $filters['to'] ?? 'Semua' }} · Nomor lot: {{ $filters['lot'] ?? 'Semua' }} · Armada: {{ $fleet?->nomor_polisi ?? 'Semua' }}</p>
    <p>Dibuat {{ now()->format('d/m/Y H:i') }} · {{ $orders->count() }} surat jalan</p>
    <table>
        <thead><tr><th>Nomor DO</th><th>Tanggal</th><th>Pelanggan</th><th>Armada</th><th>Pengemudi</th><th>Status</th><th>Lot</th><th>Jumlah rol</th><th>Berat (kg)</th><th>Penerima</th><th>Waktu diterima</th></tr></thead>
        <tbody>
            @foreach ($orders as $order)
                <tr>
                    <td>{{ $order->nomor_do }}</td><td>{{ $order->tanggal->format('d/m/Y') }}</td><td>{{ $order->customer->nama_pabrik }}</td><td>{{ $order->fleet->nomor_polisi }}</td><td>{{ $order->driver->nama }}</td><td>{{ $order->status->label() }}</td>
                    <td>{{ $order->items->pluck('roll.nomor_lot')->filter()->unique()->implode(', ') }}</td><td>{{ $order->items->count() }}</td><td>{{ str_replace('.', ',', $order->totalWeightInKg()) }}</td><td>{{ $order->proofOfDelivery?->received_by_name ?? '—' }}</td><td>{{ $order->proofOfDelivery?->received_at?->format('d/m/Y H:i') ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
