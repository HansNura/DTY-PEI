<?php

namespace Database\Factories;

use App\Enums\TrackingStatus;
use App\Enums\UserRole;
use App\Models\DeliveryOrder;
use App\Models\DeliveryTrackingEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryTrackingEvent>
 */
class DeliveryTrackingEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'delivery_order_id' => DeliveryOrder::factory(),
            'status' => fake()->randomElement(TrackingStatus::cases()),
            'latitude' => fake()->latitude(-8.5, -6.0),
            'longitude' => fake()->longitude(106.0, 112.0),
            'recorded_by' => User::factory()->state(['role' => UserRole::Driver]),
            'recorded_at' => now(),
        ];
    }
}
