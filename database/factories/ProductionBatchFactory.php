<?php

namespace Database\Factories;

use App\Models\ProductionBatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductionBatch>
 */
class ProductionBatchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nomor_merge' => '4D'.fake()->unique()->numerify('####'),
            'denier' => 150,
            'filamen' => 48,
            'intermingled' => false,
            'twist_direction' => 'Z',
            'production_date' => now()->toDateString(),
            'is_active' => false,
            'created_by' => User::factory(),
        ];
    }
}
