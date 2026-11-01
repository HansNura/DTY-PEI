<?php

namespace App\Http\Controllers\Logistics;

use App\Enums\DeliveryOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\DeliveryOrder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrackingController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        $activeStatuses = [
            DeliveryOrderStatus::Departed,
            DeliveryOrderStatus::InTransit,
            DeliveryOrderStatus::Arrived,
        ];

        $orders = DeliveryOrder::query()
            ->with(['customer', 'fleet', 'driver', 'latestTrackingEvent', 'items'])
            ->whereIn('status', $activeStatuses)
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('nomor_do', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($query) => $query->where('nama_pabrik', 'like', "%{$search}%"))
                    ->orWhereHas('fleet', fn ($query) => $query->where('nomor_polisi', 'like', "%{$search}%"))
                    ->orWhereHas('driver', fn ($query) => $query->where('nama', 'like', "%{$search}%"));
            }))
            ->latest('tanggal')
            ->get();

        $points = $orders->map(fn (DeliveryOrder $order): array => [
            'id' => $order->id,
            'nomor_do' => $order->nomor_do,
            'customer' => $order->customer->nama_pabrik,
            'fleet' => $order->fleet->nomor_polisi,
            'driver' => $order->driver->nama,
            'status' => $order->status->label(),
            'status_value' => $order->status->value,
            'last_update' => $order->latestTrackingEvent?->recorded_at?->toIso8601String(),
            'latitude' => $order->latestTrackingEvent?->latitude,
            'longitude' => $order->latestTrackingEvent?->longitude,
            'url' => route('logistics.tracking.show', $order),
        ])->values();

        $stats = [
            'total' => $orders->count(),
            'departed' => $orders->where('status', DeliveryOrderStatus::Departed)->count(),
            'in_transit' => $orders->where('status', DeliveryOrderStatus::InTransit)->count(),
            'arrived' => $orders->where('status', DeliveryOrderStatus::Arrived)->count(),
            'without_position' => $orders->filter(fn (DeliveryOrder $order): bool => $order->latestTrackingEvent?->hasCoordinates() !== true)->count(),
        ];

        return view('logistics.tracking.index', [
            'orders' => $orders,
            'points' => $points,
            'stats' => $stats,
            'search' => $search,
        ]);
    }

    public function show(DeliveryOrder $order): View
    {
        $order->load([
            'customer',
            'fleet',
            'driver',
            'items.dtyRoll',
            'trackingEvents.recordedBy',
            'latestTrackingEvent',
        ]);

        return view('logistics.tracking.show', compact('order'));
    }
}
