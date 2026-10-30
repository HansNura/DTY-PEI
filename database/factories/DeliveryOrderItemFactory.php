<?php

namespace Database\Factories;

use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\DtyRoll;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryOrderItem>
 */
class DeliveryOrderItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'delivery_order_id' => DeliveryOrder::factory(),
            'dty_roll_id' => DtyRoll::factory(),
            'berat_kg' => fake()->randomFloat(2, 0.5, 3.0),
        ];
    }
}
