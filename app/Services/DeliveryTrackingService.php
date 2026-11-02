<?php

namespace App\Services;

use App\Enums\DeliveryOrderStatus;
use App\Enums\TrackingStatus;
use App\Models\DeliveryOrder;
use App\Models\DeliveryTrackingEvent;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeliveryTrackingService
{
    public function updateStatus(
        DeliveryOrder $order,
        User $driver,
        TrackingStatus $status,
        ?float $latitude = null,
        ?float $longitude = null,
    ): DeliveryTrackingEvent {
        return DB::transaction(function () use ($order, $driver, $status, $latitude, $longitude) {
            $order = DeliveryOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->driver_id !== $driver->id) {
                throw new AuthorizationException('Anda bukan driver untuk Surat Jalan ini.');
            }

            if ($order->status === DeliveryOrderStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'status' => 'Surat Jalan sudah dibatalkan dan tidak dapat diperbarui.',
                ]);
            }

            if ($order->status === DeliveryOrderStatus::Received) {
                throw ValidationException::withMessages([
                    'status' => 'Surat Jalan sudah diterima dan tidak dapat diperbarui.',
                ]);
            }

            $this->assertTransitionAllowed($order, $status);

            $event = DeliveryTrackingEvent::create([
                'delivery_order_id' => $order->id,
                'status' => $status,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'recorded_by' => $driver->id,
                'recorded_at' => now(),
            ]);

            $order->update(['status' => $status->deliveryOrderStatus()]);

            return $event;
        });
    }

    /**
     * Compatibility wrapper for callers that pass validated request data.
     *
     * @param  array{status: string, latitude?: ?string, longitude?: ?string}  $data
     */
    public function advance(DeliveryOrder $order, User $driver, array $data): void
    {
        $this->updateStatus(
            $order,
            $driver,
            TrackingStatus::from($data['status']),
            isset($data['latitude']) ? (float) $data['latitude'] : null,
            isset($data['longitude']) ? (float) $data['longitude'] : null,
        );
    }

    private function assertTransitionAllowed(DeliveryOrder $order, TrackingStatus $status): void
    {
        if ($status === TrackingStatus::Departed) {
            if (! in_array($order->status, [DeliveryOrderStatus::Issued, DeliveryOrderStatus::Loading], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Perjalanan hanya dapat dimulai dari status Diterbitkan atau Pemuatan.',
                ]);
            }

            if ($order->total_rol < 1) {
                throw ValidationException::withMessages([
                    'status' => 'Surat Jalan belum memiliki rol yang dialokasikan.',
                ]);
            }

            if ($order->loaded_count < $order->total_rol) {
                throw ValidationException::withMessages([
                    'status' => 'Semua rol harus divalidasi oleh checker sebelum armada berangkat.',
                ]);
            }

            return;
        }

        if ($status === TrackingStatus::InTransit) {
            if (! in_array($order->status, [DeliveryOrderStatus::Departed, DeliveryOrderStatus::InTransit], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Status Dalam Perjalanan hanya dapat dipilih setelah armada berangkat.',
                ]);
            }

            return;
        }

        if (! in_array($order->status, [DeliveryOrderStatus::Departed, DeliveryOrderStatus::InTransit], true)) {
            throw ValidationException::withMessages([
                'status' => 'Status Tiba di Tujuan hanya dapat dipilih setelah armada berangkat.',
            ]);
        }
    }
}
