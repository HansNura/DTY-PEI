<?php

namespace Database\Factories;

use App\Models\DtyCarton;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DtyCarton>
 */
class DtyCartonFactory extends Factory
{
    public function definition(): array
    {
        $net = fake()->randomFloat(2, 20, 35);

        return [
            'nomor_hu' => 'ADMG'.fake()->unique()->numerify('########'),
            'nomor_merge' => '4D'.fake()->unique()->numerify('####'),
            'grade' => 'WE',
            'production_date' => now()->toDateString(),
            'denier' => 150,
            'filamen' => 48,
            'intermingled' => false,
            'twist_direction' => 'Z',
            'gross_weight_kg' => round($net + 2.5, 2),
            'net_weight_kg' => $net,
            'unit_bobbins' => 6,
            'pallet_number' => 'ADP'.fake()->numerify('######'),
            'qr_token' => bin2hex(random_bytes(32)),
            'created_by' => User::factory(),
        ];
    }
}
