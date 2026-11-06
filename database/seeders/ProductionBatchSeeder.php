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

        $batch = ProductionBatch::updateOrCreate(
            ['nomor_merge' => '4D9164'],
            [
                'denier' => 150,
                'filamen' => 48,
                'intermingled' => false,
                'twist_direction' => 'Z',
                'production_date' => '2026-11-03',
                'created_by' => $user->id,
            ],
        );

        if (! ProductionBatch::query()->active()->exists()) {
            app(ProductionBatchService::class)->activate($batch);
        }
    }
}
