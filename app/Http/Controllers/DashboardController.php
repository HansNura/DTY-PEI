<?php

namespace App\Http\Controllers;

use App\Enums\DeliveryOrderStatus;
use App\Enums\RollStatus;
use App\Enums\UserRole;
use App\Models\DeliveryOrder;
use App\Models\DtyCarton;
use App\Models\DtyRoll;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        /** @var User $user */
        $user = auth()->user();

        $data = ['user' => $user];

        if ($user->hasRole(UserRole::Admin)) {
            $data += [
                'totalUsers' => User::count(),
                'activeUsers' => User::active()->count(),
                'roleCounts' => User::query()
                    ->selectRaw('role, count(*) as total')
                    ->groupBy('role')
                    ->pluck('total', 'role'),
                'latestUsers' => User::latest()->take(5)->get(),
            ];
        } elseif ($user->hasRole(UserRole::AdminGudang)) {
            $data += [
                'rollStats' => [
                    'in_stock' => DtyRoll::where('status', RollStatus::InStock)->count(),
                    'allocated' => DtyRoll::where('status', RollStatus::Allocated)->count(),
                    'loaded' => DtyRoll::where('status', RollStatus::Loaded)->count(),
                    'cancelled' => DtyRoll::where('status', RollStatus::Cancelled)->count(),
                ],
                'recentRolls' => DtyRoll::latest()->take(5)->get(),
                'cartonsCount' => DtyCarton::count(),
                'loadingOrders' => DeliveryOrder::whereIn('status', [DeliveryOrderStatus::Issued, DeliveryOrderStatus::Loading])->count(),
            ];
        } elseif ($user->hasRole(UserRole::Logistik)) {
            $data += [
                'orderStats' => [
                    'active' => DeliveryOrder::whereIn('status', [
                        DeliveryOrderStatus::Issued,
                        DeliveryOrderStatus::Loading,
                        DeliveryOrderStatus::Departed,
                        DeliveryOrderStatus::InTransit,
                        DeliveryOrderStatus::Arrived,
                    ])->count(),
                    'loading' => DeliveryOrder::whereIn('status', [DeliveryOrderStatus::Issued, DeliveryOrderStatus::Loading])->count(),
                    'in_transit' => DeliveryOrder::whereIn('status', [DeliveryOrderStatus::Departed, DeliveryOrderStatus::InTransit])->count(),
                    'arrived' => DeliveryOrder::where('status', DeliveryOrderStatus::Arrived)->count(),
                ],
                'activeOrders' => DeliveryOrder::query()
                    ->with(['customer', 'fleet', 'driver', 'latestTrackingEvent', 'items'])
                    ->whereIn('status', [
                        DeliveryOrderStatus::Issued,
                        DeliveryOrderStatus::Loading,
                        DeliveryOrderStatus::Departed,
                        DeliveryOrderStatus::InTransit,
                        DeliveryOrderStatus::Arrived,
                    ])
                    ->latest('tanggal')
                    ->take(5)
                    ->get(),
                'recentOrders' => DeliveryOrder::with(['customer', 'fleet', 'driver'])
                    ->latest('id')
                    ->take(5)
                    ->get(),
            ];
        } else {
            $data += [
                'activeTrip' => DeliveryOrder::query()
                    ->with(['customer', 'fleet', 'items.dtyRoll', 'latestTrackingEvent'])
                    ->where('driver_id', $user->id)
                    ->whereIn('status', [DeliveryOrderStatus::Departed, DeliveryOrderStatus::InTransit])
                    ->latest('tanggal')
                    ->first(),
                'assignedOrders' => DeliveryOrder::query()
                    ->with(['customer', 'fleet', 'latestTrackingEvent'])
                    ->where('driver_id', $user->id)
                    ->latest('tanggal')
                    ->take(5)
                    ->get(),
                'todayCount' => DeliveryOrder::where('driver_id', $user->id)
                    ->whereDate('tanggal', today())
                    ->count(),
            ];
        }

        return view('dashboard', $data);
    }
}
