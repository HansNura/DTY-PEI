<?php

namespace Database\Factories;

use App\Enums\LoadScanResult;
use App\Enums\UserRole;
use App\Models\DeliveryOrder;
use App\Models\DtyRoll;
use App\Models\LoadScan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoadScan>
 */
class LoadScanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'delivery_order_id' => DeliveryOrder::factory(),
            'dty_roll_id' => DtyRoll::factory(),
            'scanned_by' => User::factory()->state(['role' => UserRole::AdminGudang]),
            'result' => LoadScanResult::Valid,
            'scanned_at' => now(),
        ];
    }
}
