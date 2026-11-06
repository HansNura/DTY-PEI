<?php

namespace Database\Seeders;

use App\Enums\RollStatus;
use App\Models\DtyCarton;
use App\Models\DtyRoll;
use App\Models\ProductionBatch;
use App\Models\User;
use Illuminate\Database\Seeder;

class DtyInventorySeeder extends Seeder
{
    private int $serial = 99000;

    public function run(): void
    {
        $user = User::query()
            ->where('email', 'gudang@pmt.test')
            ->first()
            ?? User::query()->where('role', 'admin_gudang')->first();

        if ($user === null) {
            return;
        }

        $this->seedCartons($user);
        $this->seedReadyBobbins($user);
    }

    private function seedCartons(User $user): void
    {
        $inStock = RollStatus::InStock;

        $configs = [
            ['merge' => '4D9164', 'grade' => 'AX', 'weight' => 5485, 'date' => '2026-11-04', 'statuses' => [$inStock, $inStock, $inStock, $inStock, $inStock, $inStock]],
            ['merge' => '4D9165', 'grade' => 'AX', 'weight' => 5420, 'date' => '2026-11-02', 'statuses' => [$inStock, $inStock, $inStock, $inStock, $inStock, $inStock]],
            ['merge' => '4D9166', 'grade' => 'AE', 'weight' => 5240, 'date' => '2026-10-29', 'statuses' => [$inStock, $inStock, $inStock, $inStock, $inStock, $inStock]],
            ['merge' => '4D9167', 'grade' => 'A', 'weight' => 3200, 'date' => '2026-10-21', 'statuses' => [$inStock, $inStock, $inStock, $inStock, $inStock, $inStock]],
            ['merge' => '4D9168', 'grade' => 'B', 'weight' => 780, 'date' => '2026-10-11', 'statuses' => [$inStock, $inStock, $inStock, $inStock, $inStock, $inStock]],
            ['merge' => '4D9164', 'grade' => 'AX', 'weight' => 5480, 'date' => '2026-11-05', 'statuses' => [$inStock, $inStock, $inStock, $inStock, $inStock, $inStock]],
            ['merge' => '4D9165', 'grade' => 'AE', 'weight' => 5200, 'date' => '2026-11-03', 'statuses' => [$inStock, $inStock, $inStock, $inStock, $inStock, $inStock]],
            ['merge' => '4D9167', 'grade' => 'A', 'weight' => 2500, 'date' => '2026-10-22', 'statuses' => [$inStock, $inStock, $inStock, $inStock, $inStock, $inStock]],
            ['merge' => '4D9168', 'grade' => 'B', 'weight' => 650, 'date' => '2026-10-12', 'statuses' => [$inStock, $inStock, $inStock, $inStock, $inStock, $inStock]],
        ];

        foreach ($configs as $cartonIndex => $config) {
            $batch = ProductionBatch::query()
                ->where('nomor_merge', $config['merge'])
                ->firstOrFail();

            $netKg = round($config['weight'] * 6 / 1000, 2);
            $grossKg = $config['grade'] === 'AX'
                ? min(36.25, max(34.74, round($netKg + 2.65, 2)))
                : round($netKg + 2.5, 2);

            $carton = DtyCarton::updateOrCreate(
                ['nomor_hu' => 'ADMG'.str_pad((string) (1000 + $cartonIndex), 8, '0', STR_PAD_LEFT)],
                [
                    'nomor_merge' => $batch->nomor_merge,
                    'grade' => $config['grade'],
                    'production_date' => $config['date'],
                    'denier' => $batch->denier,
                    'filamen' => $batch->filamen,
                    'intermingled' => $batch->intermingled,
                    'twist_direction' => $batch->twist_direction,
                    'gross_weight_kg' => $grossKg,
                    'net_weight_kg' => $netKg,
                    'unit_bobbins' => 6,
                    'pallet_number' => null,
                    'qr_token' => substr(hash('sha256', 'carton-'.$cartonIndex.$batch->nomor_merge), 0, 64),
                    'created_by' => $user->id,
                ],
            );

            foreach ($config['statuses'] as $status) {
                $serial = $this->nextSerial($batch);
                $kodeRol = $this->internalCode($serial);

                DtyRoll::updateOrCreate(
                    ['nomor_seri' => $serial],
                    [
                        'kode_rol' => $kodeRol,
                        'dty_carton_id' => $carton->id,
                        'production_batch_id' => $batch->id,
                        'grade' => $config['grade'],
                        'denier' => $batch->denier,
                        'filamen' => $batch->filamen,
                        'berat_gram' => $config['weight'],
                        'nomor_lot' => $batch->nomor_merge,
                        'qr_token' => substr(hash('sha256', $kodeRol), 0, 64),
                        'status' => $status,
                        'created_by' => $user->id,
                    ],
                );
            }
        }
    }

    private function seedReadyBobbins(User $user): void
    {
        $configs = [
            ['merge' => '4D9164', 'grade' => 'AX', 'weight' => 5500],
            ['merge' => '4D9164', 'grade' => 'AX', 'weight' => 5420],
            ['merge' => '4D9165', 'grade' => 'AX', 'weight' => 5380],
            ['merge' => '4D9165', 'grade' => 'AE', 'weight' => 5250],
            ['merge' => '4D9166', 'grade' => 'AE', 'weight' => 5150],
            ['merge' => '4D9166', 'grade' => 'AE', 'weight' => 5340],
            ['merge' => '4D9167', 'grade' => 'A', 'weight' => 4200],
            ['merge' => '4D9167', 'grade' => 'A', 'weight' => 1800],
            ['merge' => '4D9168', 'grade' => 'B', 'weight' => 900],
            ['merge' => '4D9168', 'grade' => 'B', 'weight' => 620],
            ['merge' => '4D9164', 'grade' => 'A', 'weight' => 3500],
            ['merge' => '4D9165', 'grade' => 'B', 'weight' => 750],
            ['merge' => '4D9164', 'grade' => 'OW', 'weight' => 5700, 'status' => RollStatus::Overweight],
            ['merge' => '4D9165', 'grade' => 'OW', 'weight' => 5820, 'status' => RollStatus::Overweight],
            ['merge' => '4D9166', 'grade' => 'WASTE', 'weight' => 450, 'status' => RollStatus::Waste],
            ['merge' => '4D9167', 'grade' => 'WASTE', 'weight' => 320, 'status' => RollStatus::Waste],
        ];

        foreach ($configs as $config) {
            $batch = ProductionBatch::query()
                ->where('nomor_merge', $config['merge'])
                ->firstOrFail();

            $serial = $this->nextSerial($batch);
            $kodeRol = $this->internalCode($serial);

            DtyRoll::updateOrCreate(
                ['nomor_seri' => $serial],
                [
                    'kode_rol' => $kodeRol,
                    'dty_carton_id' => null,
                    'production_batch_id' => $batch->id,
                    'grade' => $config['grade'],
                    'denier' => $batch->denier,
                    'filamen' => $batch->filamen,
                    'berat_gram' => $config['weight'],
                    'nomor_lot' => $batch->nomor_merge,
                    'qr_token' => substr(hash('sha256', $kodeRol), 0, 64),
                    'status' => $config['status'] ?? RollStatus::ReadyToPack,
                    'created_by' => $user->id,
                ],
            );
        }
    }

    private function internalCode(string $serial): string
    {
        return 'DTY-'.strtoupper(substr(hash('sha256', 'pmt-roll-'.$serial), 0, 26));
    }

    private function nextSerial(ProductionBatch $batch): string
    {
        $spindle = $this->serial;
        $this->serial++;

        return str_pad((string) $spindle, 5, '0', STR_PAD_LEFT)
            .'0079'
            .$batch->production_date->format('Ymd');
    }
}
