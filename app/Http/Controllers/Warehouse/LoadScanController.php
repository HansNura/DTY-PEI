<?php

namespace App\Http\Controllers\Warehouse;

use App\Enums\DeliveryOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\ScanRollRequest;
use App\Models\DeliveryOrder;
use App\Services\LoadScanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoadScanController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        $orders = DeliveryOrder::query()
            ->with(['customer', 'fleet', 'driver', 'items.dtyRoll'])
            ->whereIn('status', [DeliveryOrderStatus::Issued, DeliveryOrderStatus::Loading])
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('nomor_do', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($query) => $query->where('nama_pabrik', 'like', "%{$search}%"))
                    ->orWhereHas('fleet', fn ($query) => $query->where('nomor_polisi', 'like', "%{$search}%"));
            }))
            ->latest('tanggal')
            ->paginate(15)
            ->withQueryString();

        return view('warehouse.scans.index', compact('orders', 'search'));
    }

    public function scanner(DeliveryOrder $order): View
    {
        $order->load(['customer', 'fleet', 'driver', 'items.dtyRoll', 'loadScans.scanner', 'loadScans.dtyRoll']);

        return view('warehouse.scans.scanner', compact('order'));
    }

    public function scan(ScanRollRequest $request, DeliveryOrder $order, LoadScanService $service): JsonResponse|RedirectResponse
    {
        $qrToken = (string) $request->validated('qr_token');
        $result = $service->scan($order, $qrToken, $request->user());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => $result['success'],
                'result' => $result['result']->value,
                'result_label' => $result['result']->label(),
                'message' => $result['message'],
                'roll' => $result['roll'] ? [
                    'id' => $result['roll']->id,
                    'kode_rol' => $result['roll']->kode_rol,
                    'nomor_lot' => $result['roll']->nomor_lot,
                    'denier' => $result['roll']->denier,
                    'filamen' => $result['roll']->filamen,
                    'berat_gram' => $result['roll']->berat_gram,
                ] : null,
                'loaded_count' => $result['loaded_count'],
                'total_rol' => $result['total_rol'],
                'is_complete' => $result['is_complete'] ?? false,
            ], $result['success'] ? 200 : 422);
        }

        if ($result['success']) {
            return back()->with('status', $result['message']);
        }

        return back()->withErrors(['qr_token' => $result['message']]);
    }
}
