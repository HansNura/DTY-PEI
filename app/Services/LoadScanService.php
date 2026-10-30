<?php

namespace App\Services;

use App\Enums\DeliveryOrderStatus;
use App\Enums\LoadScanResult;
use App\Enums\RollStatus;
use App\Models\DeliveryOrder;
use App\Models\DtyRoll;
use App\Models\LoadScan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LoadScanService
{
    /**
     * @return array{
     *     success: bool,
     *     result: LoadScanResult,
     *     message: string,
     *     roll: ?DtyRoll,
     *     loaded_count: int,
     *     total_rol: int,
     *     is_complete?: bool
     * }
     */
    public function scan(DeliveryOrder $order, string $qrToken, User $checker): array
    {
        return DB::transaction(function () use ($order, $qrToken, $checker) {
            $trimmedToken = trim($qrToken);
            $roll = DtyRoll::where('qr_token', $trimmedToken)->lockForUpdate()->first();

            if (! $roll) {
                LoadScan::create([
                    'delivery_order_id' => $order->id,
                    'dty_roll_id' => null,
                    'scanned_by' => $checker->id,
                    'result' => LoadScanResult::Unknown,
                    'scanned_at' => now(),
                ]);

                return [
                    'success' => false,
                    'result' => LoadScanResult::Unknown,
                    'message' => 'Kode QR tidak dikenali di sistem PMT.',
                    'roll' => null,
                    'loaded_count' => $order->loaded_count,
                    'total_rol' => $order->total_rol,
                    'is_complete' => false,
                ];
            }

            $isAllocatedInThisDo = $order->items()->where('dty_roll_id', $roll->id)->exists();

            if (! $isAllocatedInThisDo) {
                LoadScan::create([
                    'delivery_order_id' => $order->id,
                    'dty_roll_id' => $roll->id,
                    'scanned_by' => $checker->id,
                    'result' => LoadScanResult::WrongDo,
                    'scanned_at' => now(),
                ]);

                return [
                    'success' => false,
                    'result' => LoadScanResult::WrongDo,
                    'message' => "PERINGATAN: Rol {$roll->kode_rol} (Lot {$roll->nomor_lot}) bukan alokasi untuk Surat Jalan ini!",
                    'roll' => $roll,
                    'loaded_count' => $order->loaded_count,
                    'total_rol' => $order->total_rol,
                    'is_complete' => false,
                ];
            }

            if ($roll->status === RollStatus::Loaded) {
                LoadScan::create([
                    'delivery_order_id' => $order->id,
                    'dty_roll_id' => $roll->id,
                    'scanned_by' => $checker->id,
                    'result' => LoadScanResult::Duplicate,
                    'scanned_at' => now(),
                ]);

                return [
                    'success' => false,
                    'result' => LoadScanResult::Duplicate,
                    'message' => "Rol {$roll->kode_rol} sudah pernah dipindai sebelumnya.",
                    'roll' => $roll,
                    'loaded_count' => $order->loaded_count,
                    'total_rol' => $order->total_rol,
                    'is_complete' => false,
                ];
            }

            // Mark roll as loaded
            $roll->update(['status' => RollStatus::Loaded]);

            // If order was issued, change status to loading
            if ($order->status === DeliveryOrderStatus::Issued) {
                $order->update(['status' => DeliveryOrderStatus::Loading]);
            }

            LoadScan::create([
                'delivery_order_id' => $order->id,
                'dty_roll_id' => $roll->id,
                'scanned_by' => $checker->id,
                'result' => LoadScanResult::Valid,
                'scanned_at' => now(),
            ]);

            $loadedCount = $order->fresh()->loaded_count;
            $totalRol = $order->total_rol;
            $isComplete = ($loadedCount >= $totalRol) && ($totalRol > 0);

            return [
                'success' => true,
                'result' => LoadScanResult::Valid,
                'message' => "Rol {$roll->kode_rol} berhasil divalidasi dan dimuat ke armada.",
                'roll' => $roll,
                'loaded_count' => $loadedCount,
                'total_rol' => $totalRol,
                'is_complete' => $isComplete,
            ];
        });
    }
}
