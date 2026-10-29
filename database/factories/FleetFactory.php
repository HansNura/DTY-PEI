<?php

namespace Database\Factories;

use App\Models\Fleet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fleet>
 */
class FleetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nomor_polisi' => fake()->unique()->bothify('B #### ???'),
            'jenis_kendaraan' => fake()->randomElement(['Truk Engkel', 'Truk Fuso', 'Truk Box']),
            'kapasitas_kg' => fake()->randomFloat(2, 1000, 10000),
            'driver_id' => null,
            'is_active' => true,
        ];
    }
}
