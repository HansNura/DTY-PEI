<?php

namespace Tests\Feature;

use App\Enums\DeliveryOrderStatus;
use App\Enums\TrackingStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\DeliveryTrackingEvent;
use App\Models\Fleet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveTrackingTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(DeliveryOrderStatus $status, ?DeliveryTrackingEvent $event = null): DeliveryOrder
    {
        $creator = User::factory()->create(['role' => UserRole::Logistik]);
        $driver = User::factory()->create(['role' => UserRole::Driver, 'is_active' => true]);
        $customer = Customer::factory()->create(['is_active' => true]);
        $fleet = Fleet::factory()->create(['is_active' => true, 'driver_id' => $driver->id]);

        $order = DeliveryOrder::factory()->create([
            'customer_id' => $customer->id,
            'fleet_id' => $fleet->id,
            'driver_id' => $driver->id,
            'created_by' => $creator->id,
            'status' => $status,
        ]);

        if ($event !== null) {
            $order->trackingEvents()->save($event);
        }

        return $order;
    }

    private function logisticsUser(): User
    {
        return User::factory()->create(['role' => UserRole::Logistik]);
    }

    public function test_logistics_user_can_view_tracking_map_and_active_orders(): void
    {
        $order = $this->makeOrder(DeliveryOrderStatus::InTransit, new DeliveryTrackingEvent([
            'status' => TrackingStatus::InTransit,
            'latitude' => -6.914744,
            'longitude' => 107.609810,
            'recorded_by' => User::factory()->create(['role' => UserRole::Driver])->id,
            'recorded_at' => now(),
        ]));

        $this->actingAs($this->logisticsUser())
            ->get(route('logistics.tracking.index'))
            ->assertOk()
            ->assertSee($order->nomor_do)
            ->assertSee('tracking-map')
            ->assertSee('tracking-data');
    }

    public function test_tracking_index_excludes_orders_not_yet_departed(): void
    {
        $issued = $this->makeOrder(DeliveryOrderStatus::Issued);
        $departed = $this->makeOrder(DeliveryOrderStatus::Departed);

        $this->actingAs($this->logisticsUser())
            ->get(route('logistics.tracking.index'))
            ->assertOk()
            ->assertSee($departed->nomor_do)
            ->assertDontSee($issued->nomor_do);
    }

    public function test_logistics_user_can_view_tracking_detail(): void
    {
        $order = $this->makeOrder(DeliveryOrderStatus::Departed, new DeliveryTrackingEvent([
            'status' => TrackingStatus::Departed,
            'latitude' => -6.2,
            'longitude' => 106.8,
            'recorded_by' => User::factory()->create(['role' => UserRole::Driver])->id,
            'recorded_at' => now(),
        ]));

        $this->actingAs($this->logisticsUser())
            ->get(route('logistics.tracking.show', $order))
            ->assertOk()
            ->assertSee($order->nomor_do)
            ->assertSee('Riwayat perjalanan');
    }

    public function test_tracking_detail_without_coordinates_shows_empty_state(): void
    {
        $order = $this->makeOrder(DeliveryOrderStatus::Departed);

        $this->actingAs($this->logisticsUser())
            ->get(route('logistics.tracking.show', $order))
            ->assertOk()
            ->assertSee('Belum ada koordinat GPS');
    }

    public function test_non_logistics_roles_cannot_access_tracking(): void
    {
        $order = $this->makeOrder(DeliveryOrderStatus::Departed);

        foreach ([UserRole::AdminGudang, UserRole::Driver, UserRole::Admin] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get(route('logistics.tracking.index'))->assertForbidden();
            $this->actingAs($user)->get(route('logistics.tracking.show', $order))->assertForbidden();
        }
    }
}
