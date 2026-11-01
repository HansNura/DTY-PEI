<?php

namespace Tests\Feature;

use App\Enums\DeliveryOrderStatus;
use App\Enums\RollStatus;
use App\Enums\TrackingStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\DtyRoll;
use App\Models\Fleet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private function driver(): User
    {
        return User::factory()->create(['role' => UserRole::Driver, 'is_active' => true]);
    }

    private function makeOrder(User $driver, DeliveryOrderStatus $status = DeliveryOrderStatus::Loading, RollStatus $rollStatus = RollStatus::Loaded, int $rollCount = 2): DeliveryOrder
    {
        $warehouse = User::factory()->create(['role' => UserRole::AdminGudang]);
        $creator = User::factory()->create(['role' => UserRole::Logistik]);
        $customer = Customer::factory()->create(['is_active' => true]);
        $fleet = Fleet::factory()->create(['is_active' => true, 'driver_id' => $driver->id]);

        $order = DeliveryOrder::factory()->create([
            'customer_id' => $customer->id,
            'fleet_id' => $fleet->id,
            'driver_id' => $driver->id,
            'created_by' => $creator->id,
            'status' => $status,
        ]);

        $rolls = DtyRoll::factory($rollCount)->create([
            'status' => $rollStatus,
            'created_by' => $warehouse->id,
        ]);

        foreach ($rolls as $roll) {
            DeliveryOrderItem::create([
                'delivery_order_id' => $order->id,
                'dty_roll_id' => $roll->id,
                'berat_kg' => round((float) $roll->berat_gram / 1000, 2),
            ]);
        }

        return $order;
    }

    public function test_driver_only_sees_own_deliveries(): void
    {
        $driver = $this->driver();
        $otherDriver = $this->driver();

        $ownOrder = $this->makeOrder($driver);
        $otherOrder = $this->makeOrder($otherDriver);

        $this->actingAs($driver)
            ->get(route('driver.deliveries.index'))
            ->assertOk()
            ->assertSee($ownOrder->nomor_do)
            ->assertDontSee($otherOrder->nomor_do);
    }

    public function test_driver_cannot_view_another_drivers_delivery(): void
    {
        $driver = $this->driver();
        $otherOrder = $this->makeOrder($this->driver());

        $this->actingAs($driver)
            ->get(route('driver.deliveries.show', $otherOrder))
            ->assertForbidden();
    }

    public function test_driver_can_start_journey_when_all_rolls_are_loaded(): void
    {
        $driver = $this->driver();
        $order = $this->makeOrder($driver);

        $this->actingAs($driver)
            ->post(route('driver.deliveries.status', $order), ['status' => TrackingStatus::Departed->value])
            ->assertRedirect(route('driver.deliveries.show', $order));

        $this->assertSame(DeliveryOrderStatus::Departed, $order->fresh()->status);
        $this->assertDatabaseHas('delivery_tracking_events', [
            'delivery_order_id' => $order->id,
            'status' => TrackingStatus::Departed->value,
            'recorded_by' => $driver->id,
        ]);
    }

    public function test_driver_cannot_start_journey_before_all_rolls_are_loaded(): void
    {
        $driver = $this->driver();
        $order = $this->makeOrder($driver, DeliveryOrderStatus::Loading, RollStatus::Allocated);

        $this->actingAs($driver)
            ->post(route('driver.deliveries.status', $order), ['status' => TrackingStatus::Departed->value])
            ->assertSessionHasErrors('status');

        $this->assertSame(DeliveryOrderStatus::Loading, $order->fresh()->status);
        $this->assertDatabaseCount('delivery_tracking_events', 0);
    }

    public function test_driver_can_update_location_in_transit_and_arrive(): void
    {
        $driver = $this->driver();
        $order = $this->makeOrder($driver, DeliveryOrderStatus::Departed);

        $this->actingAs($driver)->post(route('driver.deliveries.status', $order), [
            'status' => TrackingStatus::InTransit->value,
            'latitude' => -6.914744,
            'longitude' => 107.609810,
        ])->assertRedirect();

        $this->assertSame(DeliveryOrderStatus::InTransit, $order->fresh()->status);
        $this->assertDatabaseHas('delivery_tracking_events', [
            'delivery_order_id' => $order->id,
            'status' => TrackingStatus::InTransit->value,
            'latitude' => -6.914744,
            'longitude' => 107.609810,
        ]);

        $this->actingAs($driver)
            ->post(route('driver.deliveries.status', $order), ['status' => TrackingStatus::Arrived->value])
            ->assertRedirect();

        $this->assertSame(DeliveryOrderStatus::Arrived, $order->fresh()->status);
        $this->assertSame(2, $order->trackingEvents()->count());
    }

    public function test_driver_cannot_skip_to_arrived_from_loading(): void
    {
        $driver = $this->driver();
        $order = $this->makeOrder($driver);

        $this->actingAs($driver)
            ->post(route('driver.deliveries.status', $order), ['status' => TrackingStatus::Arrived->value])
            ->assertSessionHasErrors('status');

        $this->assertSame(DeliveryOrderStatus::Loading, $order->fresh()->status);
    }

    public function test_coordinates_must_be_sent_together(): void
    {
        $driver = $this->driver();
        $order = $this->makeOrder($driver, DeliveryOrderStatus::Departed);

        $this->actingAs($driver)->post(route('driver.deliveries.status', $order), [
            'status' => TrackingStatus::InTransit->value,
            'latitude' => -6.9,
        ])->assertSessionHasErrors('longitude');

        $this->actingAs($driver)->post(route('driver.deliveries.status', $order), [
            'status' => TrackingStatus::InTransit->value,
            'latitude' => -120,
            'longitude' => 200,
        ])->assertSessionHasErrors(['latitude', 'longitude']);
    }

    public function test_driver_cannot_update_another_drivers_order(): void
    {
        $driver = $this->driver();
        $order = $this->makeOrder($this->driver());

        $this->actingAs($driver)
            ->post(route('driver.deliveries.status', $order), ['status' => TrackingStatus::Departed->value])
            ->assertForbidden();

        $this->assertSame(DeliveryOrderStatus::Loading, $order->fresh()->status);
    }

    public function test_cancelled_order_cannot_be_updated(): void
    {
        $driver = $this->driver();
        $order = $this->makeOrder($driver, DeliveryOrderStatus::Cancelled);

        $this->actingAs($driver)
            ->post(route('driver.deliveries.status', $order), ['status' => TrackingStatus::Departed->value])
            ->assertSessionHasErrors('status');

        $this->assertSame(DeliveryOrderStatus::Cancelled, $order->fresh()->status);
    }

    public function test_non_driver_roles_cannot_access_driver_routes(): void
    {
        $order = $this->makeOrder($this->driver());

        foreach ([UserRole::AdminGudang, UserRole::Logistik, UserRole::Admin] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('driver.deliveries.index'))
                ->assertForbidden();
        }
    }
}
