<?php

namespace Database\Seeders;

use App\Models\ProductionBatch;
use App\Models\User;
use App\Services\ProductionBatchService;
use Illuminate\Database\Seeder;

class ProductionBatchSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()
            ->where('email', 'gudang@pmt.test')
            ->first()
            ?? User::query()->where('role', 'admin_gudang')->first();

        if ($user === null) {
            return;
        }

        $batches = [
            ['nomor_merge' => '4D9164', 'denier' => 150, 'filamen' => 48, 'intermingled' => false, 'twist_direction' => 'Z', 'production_date' => '2026-11-03'],
            ['nomor_merge' => '4D9165', 'denier' => 150, 'filamen' => 48, 'intermingled' => false, 'twist_direction' => 'Z', 'production_date' => '2026-11-01'],
            ['nomor_merge' => '4D9166', 'denier' => 150, 'filamen' => 48, 'intermingled' => false, 'twist_direction' => 'Z', 'production_date' => '2026-10-28'],
            ['nomor_merge' => '4D9167', 'denier' => 150, 'filamen' => 48, 'intermingled' => false, 'twist_direction' => 'S', 'production_date' => '2026-10-20'],
            ['nomor_merge' => '4D9168', 'denier' => 300, 'filamen' => 72, 'intermingled' => true, 'twist_direction' => 'Z', 'production_date' => '2026-10-10'],
        ];

        $created = [];

        foreach ($batches as $batch) {
            $created[$batch['nomor_merge']] = ProductionBatch::updateOrCreate(
                ['nomor_merge' => $batch['nomor_merge']],
                [
                    ...$batch,
                    'created_by' => $user->id,
                ],
            );
        }

        if (! ProductionBatch::query()->active()->exists()) {
            app(ProductionBatchService::class)->activate($created['4D9164']);
        }
    }
}
