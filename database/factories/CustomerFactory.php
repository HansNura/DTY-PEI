<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama_pabrik' => fake()->company(),
            'alamat' => fake()->address(),
            'kontak_nama' => fake()->name(),
            'kontak_telepon' => fake()->phoneNumber(),
            'latitude' => fake()->latitude(-90, 90),
            'longitude' => fake()->longitude(-180, 180),
            'is_active' => true,
        ];
    }
}
