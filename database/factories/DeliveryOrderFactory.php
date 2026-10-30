<?php

namespace Database\Factories;

use App\Enums\DeliveryOrderStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\Fleet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryOrder>
 */
class DeliveryOrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nomor_do' => 'DO-'.date('Ymd').'-'.strtoupper(fake()->bothify('####')),
            'customer_id' => Customer::factory(),
            'fleet_id' => Fleet::factory(),
            'driver_id' => User::factory()->state(['role' => UserRole::Driver]),
            'tanggal' => now()->toDateString(),
            'status' => DeliveryOrderStatus::Issued,
            'catatan' => fake()->optional()->sentence(),
            'created_by' => User::factory()->state(['role' => UserRole::Logistik]),
        ];
    }
}
