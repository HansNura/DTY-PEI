<?php

namespace App\Services;

use App\Enums\DeliveryOrderStatus;
use App\Enums\RollStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\DtyRoll;
use App\Models\Fleet;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeliveryOrderService
{
    public function generateDoNumber(): string
    {
        $prefix = 'DO-'.date('Ymd').'-';
        $latest = DeliveryOrder::where('nomor_do', 'like', $prefix.'%')->latest('id')->first();

        $nextNumber = 1;
        if ($latest) {
            $suffix = substr($latest->nomor_do, strlen($prefix));
            $nextNumber = ((int) $suffix) + 1;
        }

        return $prefix.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>  $rollIds
     */
    public function createDeliveryOrder(array $data, array $rollIds, User $creator): DeliveryOrder
    {
        return DB::transaction(function () use ($data, $rollIds, $creator) {
            $customer = Customer::query()->lockForUpdate()->findOrFail($data['customer_id']);
            $fleet = Fleet::query()->lockForUpdate()->findOrFail($data['fleet_id']);
            $driver = User::query()->findOrFail($data['driver_id']);
            $rolls = DtyRoll::whereIn('id', $rollIds)
                ->lockForUpdate()
                ->get();

            if ($rolls->count() !== count($rollIds) || count($rollIds) !== count(array_unique($rollIds))) {
                throw ValidationException::withMessages([
                    'rolls' => 'Beberapa rol yang dipilih tidak ditemukan.',
                ]);
            }

            if (! $customer->is_active) {
                throw ValidationException::withMessages(['customer_id' => 'Pelanggan sudah tidak aktif.']);
            }

            if (! $fleet->is_active) {
                throw ValidationException::withMessages(['fleet_id' => 'Armada sudah tidak aktif.']);
            }

            if (! $driver->is_active || ! $driver->hasRole(UserRole::Driver)) {
                throw ValidationException::withMessages(['driver_id' => 'Pengemudi harus merupakan akun driver yang aktif.']);
            }

            foreach ($rolls as $roll) {
                if ($roll->status !== RollStatus::InStock) {
                    throw ValidationException::withMessages([
                        'rolls' => "Rol {$roll->kode_rol} tidak lagi berstatus tersedia dalam stok.",
                    ]);
                }
            }

            $totalKg = round($rolls->sum(fn (DtyRoll $roll): float => (float) $roll->berat_gram / 1000), 2);

            if ((float) $fleet->kapasitas_kg > 0 && $totalKg > (float) $fleet->kapasitas_kg) {
                throw ValidationException::withMessages([
                    'rolls' => sprintf(
                        'Total berat %.2f kg melebihi kapasitas armada %s (%.2f kg).',
                        $totalKg,
                        $fleet->nomor_polisi,
                        (float) $fleet->kapasitas_kg,
                    ),
                ]);
            }

            $order = DeliveryOrder::create([
                'nomor_do' => $data['nomor_do'] ?? $this->generateDoNumber(),
                'customer_id' => $data['customer_id'],
                'fleet_id' => $data['fleet_id'],
                'driver_id' => $data['driver_id'],
                'tanggal' => $data['tanggal'],
                'catatan' => $data['catatan'] ?? null,
                'status' => DeliveryOrderStatus::Issued,
                'created_by' => $creator->id,
            ]);

            foreach ($rolls as $roll) {
                DeliveryOrderItem::create([
                    'delivery_order_id' => $order->id,
                    'dty_roll_id' => $roll->id,
                    'berat_kg' => $roll->weightInKg(),
                ]);

                $roll->update(['status' => RollStatus::Allocated]);
            }

            return $order;
        });
    }

    public function cancel(DeliveryOrder $order): DeliveryOrder
    {
        return DB::transaction(function () use ($order) {
            if ($order->status === DeliveryOrderStatus::Cancelled) {
                return $order;
            }

            if (in_array($order->status, [DeliveryOrderStatus::Departed, DeliveryOrderStatus::InTransit, DeliveryOrderStatus::Arrived, DeliveryOrderStatus::Received])) {
                throw ValidationException::withMessages([
                    'status' => 'Surat Jalan yang sudah dalam perjalanan atau terkirim tidak dapat dibatalkan.',
                ]);
            }

            $order->update(['status' => DeliveryOrderStatus::Cancelled]);

            foreach ($order->items()->with('dtyRoll')->get() as $item) {
                if ($item->dtyRoll && $item->dtyRoll->status !== RollStatus::Cancelled) {
                    $item->dtyRoll->update(['status' => RollStatus::InStock]);
                }
            }

            return $order;
        });
    }
}
