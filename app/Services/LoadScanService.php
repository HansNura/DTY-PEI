<?php

namespace App\Services;

use App\Enums\DeliveryOrderStatus;
use App\Enums\LoadScanResult;
use App\Enums\RollStatus;
use App\Models\DeliveryOrder;
use App\Models\DtyCarton;
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
     *     carton: ?DtyCarton,
     *     roll: ?DtyRoll,
     *     loaded_count: int,
     *     total_rol: int,
     *     loaded_box_count: int,
     *     total_box: int,
     *     is_complete?: bool
     * }
     */
    public function scan(DeliveryOrder $order, string $token, User $checker): array
    {
        return DB::transaction(function () use ($order, $token, $checker) {
            $order = DeliveryOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! in_array($order->status, [DeliveryOrderStatus::Issued, DeliveryOrderStatus::Loading], true)) {
                return $this->result(
                    $order,
                    LoadScanResult::InvalidOrder,
                    "Surat Jalan {$order->nomor_do} sudah tidak dalam tahap pemuatan (status: {$order->status->label()}).",
                );
            }

            $trimmed = trim($token);
            $normalized = strtoupper($trimmed);

            $carton = DtyCarton::query()
                ->where(function ($query) use ($trimmed, $normalized): void {
                    $query->where('nomor_hu', $trimmed)
                        ->orWhere('nomor_hu', $normalized)
                        ->orWhere('qr_token', $trimmed);
                })
                ->lockForUpdate()
                ->first();

            if ($carton === null) {
                $roll = DtyRoll::query()
                    ->where(fn ($query) => $query
                        ->where('qr_token', $trimmed)
                        ->orWhere('nomor_seri', $trimmed))
                    ->first();

                if ($roll !== null) {
                    LoadScan::create([
                        'delivery_order_id' => $order->id,
                        'dty_roll_id' => $roll->id,
                        'scanned_by' => $checker->id,
                        'result' => LoadScanResult::WrongUnit,
                        'scanned_at' => now(),
                    ]);

                    return $this->result(
                        $order,
                        LoadScanResult::WrongUnit,
                        "QR ini milik bobbin {$roll->nomor_seri}. Scanner muatan memvalidasi kardus; scan QR atau masukkan nomor HU kardus.",
                        roll: $roll,
                    );
                }

                LoadScan::create([
                    'delivery_order_id' => $order->id,
                    'dty_roll_id' => null,
                    'scanned_by' => $checker->id,
                    'result' => LoadScanResult::Unknown,
                    'scanned_at' => now(),
                ]);

                return $this->result($order, LoadScanResult::Unknown, 'Kode QR / nomor HU kardus tidak dikenali di sistem PMT.');
            }

            $cartonRolls = $carton->rolls()->lockForUpdate()->get();
            $orderRollIds = $order->items()->pluck('dty_roll_id')->map(fn ($id): int => (int) $id);

            if ($cartonRolls->isEmpty()
                || $cartonRolls->pluck('id')->diff($orderRollIds)->isNotEmpty()
                || $orderRollIds->intersect($cartonRolls->pluck('id'))->count() !== $cartonRolls->count()) {
                $this->record($order, $checker, LoadScanResult::WrongDo, carton: $carton);

                return $this->result(
                    $order,
                    LoadScanResult::WrongDo,
                    "Kardus {$carton->nomor_hu} bukan bagian dari Surat Jalan ini.",
                    carton: $carton,
                );
            }

            if ($cartonRolls->contains(fn (DtyRoll $roll): bool => ! in_array($roll->status, [RollStatus::Allocated, RollStatus::Loaded], true))) {
                $this->record($order, $checker, LoadScanResult::WrongDo, carton: $carton);

                return $this->result(
                    $order,
                    LoadScanResult::WrongDo,
                    "Ada bobbin dalam kardus {$carton->nomor_hu} yang tidak siap dimuat.",
                    carton: $carton,
                );
            }

            if ($cartonRolls->every(fn (DtyRoll $roll): bool => $roll->status === RollStatus::Loaded)) {
                $this->record($order, $checker, LoadScanResult::Duplicate, carton: $carton);

                return $this->result(
                    $order,
                    LoadScanResult::Duplicate,
                    "Kardus {$carton->nomor_hu} sudah pernah dipindai sebelumnya.",
                    carton: $carton,
                );
            }

            DtyRoll::query()
                ->whereIn('id', $cartonRolls->pluck('id'))
                ->update(['status' => RollStatus::Loaded->value]);

            if ($order->status === DeliveryOrderStatus::Issued) {
                $order->update(['status' => DeliveryOrderStatus::Loading]);
            }

            $this->record($order, $checker, LoadScanResult::Valid, carton: $carton);

            return $this->result(
                $order,
                LoadScanResult::Valid,
                "Kardus {$carton->nomor_hu} berhasil divalidasi. {$cartonRolls->count()} bobbin dimuat ke armada.",
                carton: $carton,
            );
        });
    }

    /**
     * @return array{
     *     success: bool,
     *     result: LoadScanResult,
     *     message: string,
     *     carton: ?DtyCarton,
     *     roll: ?DtyRoll,
     *     loaded_count: int,
     *     total_rol: int,
     *     loaded_box_count: int,
     *     total_box: int,
     *     is_complete: bool
     * }
     */
    private function result(
        DeliveryOrder $order,
        LoadScanResult $result,
        string $message,
        ?DtyCarton $carton = null,
        ?DtyRoll $roll = null,
    ): array {
        $order->load('items.dtyRoll.carton');

        $items = $order->items;
        $totalRol = $items->count();
        $loadedCount = $items->filter(fn ($item): bool => $item->dtyRoll?->status === RollStatus::Loaded)->count();
        $cartonGroups = $items
            ->filter(fn ($item): bool => $item->dtyRoll?->carton !== null)
            ->groupBy(fn ($item): int => $item->dtyRoll->carton->id);
        $totalBox = $cartonGroups->count();
        $loadedBoxCount = $cartonGroups
            ->filter(fn ($group): bool => $group->every(fn ($item): bool => $item->dtyRoll->status === RollStatus::Loaded))
            ->count();

        return [
            'success' => $result === LoadScanResult::Valid,
            'result' => $result,
            'message' => $message,
            'carton' => $carton,
            'roll' => $roll,
            'loaded_count' => $loadedCount,
            'total_rol' => $totalRol,
            'loaded_box_count' => $loadedBoxCount,
            'total_box' => $totalBox,
            'is_complete' => $totalRol > 0 && $loadedCount >= $totalRol,
        ];
    }

    private function record(
        DeliveryOrder $order,
        User $checker,
        LoadScanResult $result,
        ?DtyCarton $carton = null,
        ?DtyRoll $roll = null,
    ): void {
        LoadScan::create([
            'delivery_order_id' => $order->id,
            'dty_roll_id' => $roll?->id,
            'dty_carton_id' => $carton?->id,
            'scanned_by' => $checker->id,
            'result' => $result,
            'scanned_at' => now(),
        ]);
    }
}
