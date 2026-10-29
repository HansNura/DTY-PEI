<?php

namespace Database\Factories;

use App\Enums\RollStatus;
use App\Models\DtyRoll;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DtyRoll>
 */
class DtyRollFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kode_rol' => 'DTY-'.strtoupper((string) Str::ulid()),
            'denier' => fake()->numberBetween(75, 1200),
            'filamen' => fake()->numberBetween(12, 144),
            'berat_gram' => fake()->randomFloat(2, 500, 3000),
            'nomor_lot' => fake()->bothify('LOT-########'),
            'qr_token' => bin2hex(random_bytes(32)),
            'status' => RollStatus::InStock,
            'created_by' => User::factory(),
        ];
    }
}
