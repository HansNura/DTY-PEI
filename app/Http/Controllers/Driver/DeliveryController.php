<?php

namespace App\Http\Controllers\Driver;

use App\Enums\DeliveryOrderStatus;
use App\Enums\TrackingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\UpdateDeliveryStatusRequest;
use App\Models\DeliveryOrder;
use App\Services\DeliveryTrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeliveryController extends Controller
{
    public function index(Request $request): View
    {
        $status = (string) $request->query('status');

        $orders = DeliveryOrder::query()
            ->with(['customer', 'fleet', 'latestTrackingEvent'])
            ->where('driver_id', $request->user()->id)
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderByRaw("CASE WHEN status IN ('departed', 'in_transit', 'arrived') THEN 0 ELSE 1 END")
            ->latest('tanggal')
            ->paginate(15)
            ->withQueryString();

        return view('driver.deliveries.index', [
            'orders' => $orders,
            'status' => $status,
            'statuses' => DeliveryOrderStatus::cases(),
        ]);
    }

    public function show(Request $request, DeliveryOrder $order): View
    {
        abort_unless($order->driver_id === $request->user()->id, 403, 'Anda tidak ditugaskan pada Surat Jalan ini.');

        $order->load([
            'customer',
            'fleet',
            'driver',
            'items.dtyRoll',
            'trackingEvents.recordedBy',
            'latestTrackingEvent',
        ]);

        return view('driver.deliveries.show', [
            'order' => $order,
            'actions' => $this->availableActions($order),
        ]);
    }

    public function updateStatus(
        UpdateDeliveryStatusRequest $request,
        DeliveryOrder $order,
        DeliveryTrackingService $service,
    ): RedirectResponse {
        $validated = $request->validated();

        $event = $service->updateStatus(
            $order,
            $request->user(),
            TrackingStatus::from($validated['status']),
            isset($validated['latitude']) ? (float) $validated['latitude'] : null,
            isset($validated['longitude']) ? (float) $validated['longitude'] : null,
        );

        return redirect()->route('driver.deliveries.show', $order)
            ->with('status', "Status perjalanan diperbarui: {$event->status->label()}.");
    }

    /**
     * @return array<int, TrackingStatus>
     */
    private function availableActions(DeliveryOrder $order): array
    {
        if ($order->status === DeliveryOrderStatus::Cancelled || $order->status === DeliveryOrderStatus::Received) {
            return [];
        }

        if (in_array($order->status, [DeliveryOrderStatus::Issued, DeliveryOrderStatus::Loading], true)) {
            return $order->loaded_count >= $order->total_rol
                ? [TrackingStatus::Departed]
                : [];
        }

        return match ($order->status) {
            DeliveryOrderStatus::Departed => [TrackingStatus::InTransit, TrackingStatus::Arrived],
            DeliveryOrderStatus::InTransit => [TrackingStatus::InTransit, TrackingStatus::Arrived],
            default => [],
        };
    }
}
