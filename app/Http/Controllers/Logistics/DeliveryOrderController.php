<?php

namespace App\Http\Controllers\Logistics;

use App\Enums\DeliveryOrderStatus;
use App\Enums\RollStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Logistics\SaveDeliveryOrderRequest;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\DtyRoll;
use App\Models\Fleet;
use App\Models\User;
use App\Services\DeliveryOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeliveryOrderController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $status = (string) $request->query('status');

        $orders = DeliveryOrder::query()
            ->with(['customer', 'fleet', 'driver', 'creator', 'items.dtyRoll'])
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('nomor_do', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($query) => $query->where('nama_pabrik', 'like', "%{$search}%"))
                    ->orWhereHas('fleet', fn ($query) => $query->where('nomor_polisi', 'like', "%{$search}%"))
                    ->orWhereHas('driver', fn ($query) => $query->where('nama', 'like', "%{$search}%"));
            }))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $statuses = DeliveryOrderStatus::cases();

        return view('logistics.orders.index', compact('orders', 'search', 'status', 'statuses'));
    }

    public function create(): View
    {
        $customers = Customer::where('is_active', true)->orderBy('nama_pabrik')->get();
        $fleets = Fleet::where('is_active', true)->with('driver')->orderBy('nomor_polisi')->get();
        $drivers = User::where('role', UserRole::Driver)->where('is_active', true)->orderBy('nama')->get();
        $availableRolls = DtyRoll::where('status', RollStatus::InStock)->orderBy('nomor_lot')->orderBy('kode_rol')->get();

        return view('logistics.orders.create', compact('customers', 'fleets', 'drivers', 'availableRolls'));
    }

    public function store(SaveDeliveryOrderRequest $request, DeliveryOrderService $service): RedirectResponse
    {
        $validated = $request->validated();
        $rollIds = array_map('intval', $validated['rolls']);

        $order = $service->createDeliveryOrder($validated, $rollIds, $request->user());

        return redirect()->route('logistics.orders.show', $order)
            ->with('status', "Surat Jalan {$order->nomor_do} berhasil diterbitkan dengan ".count($rollIds).' rol benang.');
    }

    public function show(DeliveryOrder $order): View
    {
        $order->load(['customer', 'fleet', 'driver', 'creator', 'items.dtyRoll', 'loadScans.scanner']);

        return view('logistics.orders.show', compact('order'));
    }

    public function print(DeliveryOrder $order): View
    {
        $order->load(['customer', 'fleet', 'driver', 'items.dtyRoll']);

        return view('logistics.orders.print', compact('order'));
    }

    public function cancel(DeliveryOrder $order, DeliveryOrderService $service): RedirectResponse
    {
        $service->cancel($order);

        return redirect()->route('logistics.orders.show', $order)
            ->with('status', "Surat Jalan {$order->nomor_do} telah dibatalkan dan rol dikembalikan ke stok gudang.");
    }
}
