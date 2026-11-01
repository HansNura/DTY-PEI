<?php

namespace App\Http\Controllers\Driver;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\ConfirmDeliveryOrderRequest;
use App\Http\Requests\Driver\UpdateDeliveryOrderStatusRequest;
use App\Models\DeliveryOrder;
use App\Services\DeliveryTrackingService;
use App\Services\ProofOfDeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DeliveryController extends Controller
{
    public function index(Request $request): View
    {
        return view('driver.deliveries.index', [
            'orders' => DeliveryOrder::query()
                ->with(['customer', 'fleet', 'proofOfDelivery'])
                ->where('driver_id', $request->user()->id)
                ->orderByDesc('tanggal')
                ->orderByDesc('id')
                ->paginate(15),
        ]);
    }

    public function show(Request $request, DeliveryOrder $deliveryOrder): View
    {
        $this->authorizeDriver($deliveryOrder, $request);

        return view('driver.deliveries.show', [
            'order' => $deliveryOrder->load([
                'customer',
                'fleet',
                'items.roll',
                'trackingEvents',
                'proofOfDelivery',
            ]),
        ]);
    }

    public function updateStatus(
        UpdateDeliveryOrderStatusRequest $request,
        DeliveryOrder $deliveryOrder,
        DeliveryTrackingService $service
    ): RedirectResponse {
        $service->advance($deliveryOrder, $request->user(), $request->validated());

        return back()->with('status', 'Status perjalanan berhasil diperbarui.');
    }

    public function confirm(
        ConfirmDeliveryOrderRequest $request,
        DeliveryOrder $deliveryOrder,
        ProofOfDeliveryService $service
    ): RedirectResponse {
        $this->authorizeDriver($deliveryOrder, $request);
        try {
            $service->confirm($deliveryOrder, $request->user(), $request->validated());
        } catch (ValidationException $exception) {
            return back()
                ->withErrors($exception->errors())
                ->withInput($request->except('signature'));
        }

        return redirect()->route('driver.deliveries.show', $deliveryOrder)
            ->with('status', 'Penerimaan dan tanda tangan pelanggan berhasil disimpan.');
    }

    private function authorizeDriver(DeliveryOrder $order, Request $request): void
    {
        abort_unless(
            $request->user()?->hasRole(UserRole::Driver) && $order->driver_id === $request->user()->id,
            403,
            'Surat jalan ini bukan tanggung jawab Anda.'
        );
    }
}
