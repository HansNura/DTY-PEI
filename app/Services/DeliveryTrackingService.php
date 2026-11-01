<?php

namespace App\Services;

use App\Models\DeliveryOrder;
use App\Models\DeliveryTrackingEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeliveryTrackingService
{
    /**
     * @param  array{status: string, latitude?: ?string, longitude?: ?string}  $data
     */
    public function advance(DeliveryOrder $order, User $driver, array $data): void
    {
        DB::transaction(function () use ($order, $driver, $data): void {
            $lockedOrder = DeliveryOrder::query()->lockForUpdate()->findOrFail($order->id);

            if ($lockedOrder->driver_id !== $driver->id) {
                abort(403, 'Surat jalan ini bukan tanggung jawab Anda.');
            }

            $nextStatus = $lockedOrder->status->next();

            if ($nextStatus === null || $nextStatus->value !== $data['status']) {
                throw ValidationException::withMessages(['status' => 'Perbarui status perjalanan sesuai urutan yang tersedia.']);
            }

            $recordedAt = now();
            $lockedOrder->update(['status' => $nextStatus]);
            DeliveryTrackingEvent::create([
                'delivery_order_id' => $lockedOrder->id,
                'status' => $nextStatus,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'recorded_by' => $driver->id,
                'recorded_at' => $recordedAt,
            ]);
        });
    }
}
