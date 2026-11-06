<?php

namespace App\Services;

use App\Models\ProductionBatch;
use Illuminate\Support\Facades\DB;

class ProductionBatchService
{
    public function active(): ?ProductionBatch
    {
        return ProductionBatch::query()->active()->latest('id')->first();
    }

    public function activate(ProductionBatch $batch): ProductionBatch
    {
        return DB::transaction(function () use ($batch): ProductionBatch {
            ProductionBatch::query()
                ->whereKeyNot($batch->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $batch->update(['is_active' => true]);

            return $batch;
        });
    }
}
