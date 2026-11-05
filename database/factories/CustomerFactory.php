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
            'kode_pelanggan' => 'CUST-'.fake()->unique()->numerify('####'),
            'nama_pabrik' => fake()->company(),
            'email' => fake()->unique()->companyEmail(),
            'npwp' => fake()->numerify('##.###.###.#-###.###'),
            'alamat' => fake()->address(),
            'kota' => fake()->city(),
            'provinsi' => fake()->state(),
            'kode_pos' => fake()->postcode(),
            'kontak_nama' => fake()->name(),
            'kontak_jabatan' => fake()->jobTitle(),
            'kontak_telepon' => fake()->phoneNumber(),
            'latitude' => fake()->latitude(-90, 90),
            'longitude' => fake()->longitude(-180, 180),
            'is_active' => true,
        ];
    }
}
