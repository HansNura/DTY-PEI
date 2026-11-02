<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Logistics\DeliveryReportRequest;
use App\Models\Fleet;
use App\Services\DeliveryReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DeliveryReportController extends Controller
{
    public function index(DeliveryReportRequest $request, DeliveryReportService $service): View
    {
        $filters = $request->validated();

        return view('logistics.reports.index', [
            'orders' => $service->orders($filters),
            'fleets' => Fleet::query()->orderBy('nomor_polisi')->get(['id', 'nomor_polisi']),
            'filters' => $filters,
        ]);
    }

    public function pdf(DeliveryReportRequest $request, DeliveryReportService $service): Response
    {
        $filters = $request->validated();
        $orders = $service->orders($filters);
        $fleet = isset($filters['fleet_id']) ? Fleet::query()->find($filters['fleet_id']) : null;

        return Pdf::loadView('logistics.reports.pdf', compact('orders', 'filters', 'fleet'))
            ->setPaper('a4', 'landscape')
            ->download('rekap-pengiriman-'.now()->format('Ymd-His').'.pdf');
    }

    public function excel(DeliveryReportRequest $request, DeliveryReportService $service): StreamedResponse
    {
        $filters = $request->validated();
        $orders = $service->orders($filters);
        $fleet = isset($filters['fleet_id']) ? Fleet::query()->find($filters['fleet_id']) : null;
        $filename = 'rekap-pengiriman-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(function () use ($orders, $filters, $fleet): void {
            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Rekap Pengiriman');
            $sheet->setCellValue('A1', 'REKAPITULASI PENGIRIMAN DTY — PMT');
            $sheet->setCellValue('A2', 'Periode: '.($filters['from'] ?? 'Semua').' s.d. '.($filters['to'] ?? 'Semua'));
            $sheet->setCellValue('A3', 'Nomor lot: '.($filters['lot'] ?? 'Semua').' | Armada: '.($fleet?->nomor_polisi ?? 'Semua'));
            $headers = ['Nomor DO', 'Tanggal', 'Pelanggan', 'Armada', 'Pengemudi', 'Status', 'Nomor lot', 'Jumlah rol', 'Berat (kg)', 'Penerima', 'Waktu diterima'];

            foreach ($headers as $index => $header) {
                $sheet->setCellValueExplicit([$index + 1, 5], $header, DataType::TYPE_STRING);
            }

            foreach ($orders as $index => $order) {
                $row = $index + 6;
                $values = [
                    $order->nomor_do,
                    $order->tanggal->format('Y-m-d'),
                    $order->customer->nama_pabrik,
                    $order->fleet->nomor_polisi,
                    $order->driver->nama,
                    $order->status->label(),
                    $order->items->pluck('roll.nomor_lot')->filter()->unique()->implode(', '),
                    (string) $order->items->count(),
                    $order->totalWeightInKg(),
                    $order->proofOfDelivery?->received_by_name ?? '',
                    $order->proofOfDelivery?->received_at?->format('Y-m-d H:i:s') ?? '',
                ];

                foreach ($values as $column => $value) {
                    $sheet->setCellValueExplicit([$column + 1, $row], (string) $value, DataType::TYPE_STRING);
                }
            }

            $sheet->getStyle('A5:K5')->getFont()->setBold(true);
            $sheet->getStyle('A1:K1')->getFont()->setBold(true);
            $sheet->setAutoFilter('A5:K'.max(5, $orders->count() + 5));

            foreach (range('A', 'K') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
