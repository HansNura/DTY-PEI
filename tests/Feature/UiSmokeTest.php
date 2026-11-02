<?php

namespace Tests\Feature;

use App\Enums\DeliveryOrderStatus;
use App\Enums\RollStatus;
use App\Enums\TrackingStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\DeliveryTrackingEvent;
use App\Models\DtyRoll;
use App\Models\Fleet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function user(UserRole $role): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    private function order(User $driver, DeliveryOrderStatus $status = DeliveryOrderStatus::Loading, int $rollCount = 2): DeliveryOrder
    {
        $warehouse = $this->user(UserRole::AdminGudang);
        $creator = $this->user(UserRole::Logistik);
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
            'status' => $status === DeliveryOrderStatus::Loading ? RollStatus::Loaded : RollStatus::Allocated,
            'created_by' => $warehouse->id,
        ]);

        foreach ($rolls as $roll) {
            DeliveryOrderItem::create([
                'delivery_order_id' => $order->id,
                'dty_roll_id' => $roll->id,
                'berat_kg' => 1.2,
            ]);
        }

        return $order;
    }

    public function test_guest_pages_render(): void
    {
        $this->get(route('home'))->assertOk();
        $this->get(route('login'))->assertOk()->assertSee('Masuk ke PMT');
    }

    public function test_admin_pages_render(): void
    {
        $admin = $this->user(UserRole::Admin);
        $other = $this->user(UserRole::Driver);

        $pages = [
            route('dashboard'),
            route('admin.users.index'),
            route('admin.users.create'),
            route('admin.users.edit', $other),
            route('profile.edit'),
        ];

        foreach ($pages as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_warehouse_pages_render(): void
    {
        $warehouse = $this->user(UserRole::AdminGudang);
        $roll = DtyRoll::factory()->create(['status' => RollStatus::InStock, 'created_by' => $warehouse->id]);
        $order = $this->order($this->user(UserRole::Driver), DeliveryOrderStatus::Issued);

        $pages = [
            route('dashboard'),
            route('warehouse.rolls.index'),
            route('warehouse.rolls.create'),
            route('warehouse.rolls.edit', $roll),
            route('warehouse.rolls.label', $roll),
            route('warehouse.scans.index'),
            route('warehouse.scans.scanner', $order),
        ];

        foreach ($pages as $url) {
            $this->actingAs($warehouse)->get($url)->assertOk();
        }
    }

    public function test_logistics_pages_render(): void
    {
        $logistics = $this->user(UserRole::Logistik);
        $driver = $this->user(UserRole::Driver);
        $customer = Customer::factory()->create(['is_active' => true]);
        $fleet = Fleet::factory()->create(['is_active' => true, 'driver_id' => $driver->id]);
        $order = $this->order($driver, DeliveryOrderStatus::InTransit);

        DeliveryTrackingEvent::create([
            'delivery_order_id' => $order->id,
            'status' => TrackingStatus::InTransit,
            'latitude' => -6.9,
            'longitude' => 107.6,
            'recorded_by' => $driver->id,
            'recorded_at' => now(),
        ]);

        $pages = [
            route('dashboard'),
            route('logistics.customers.index'),
            route('logistics.customers.create'),
            route('logistics.customers.edit', $customer),
            route('logistics.fleets.index'),
            route('logistics.fleets.create'),
            route('logistics.fleets.edit', $fleet),
            route('logistics.orders.index'),
            route('logistics.orders.create'),
            route('logistics.orders.show', $order),
            route('logistics.orders.print', $order),
            route('logistics.tracking.index'),
            route('logistics.tracking.show', $order),
        ];

        foreach ($pages as $url) {
            $this->actingAs($logistics)->get($url)->assertOk();
        }
    }

    public function test_driver_pages_render(): void
    {
        $driver = $this->user(UserRole::Driver);
        $order = $this->order($driver, DeliveryOrderStatus::Departed);

        $this->actingAs($driver)->get(route('dashboard'))->assertOk();
        $this->actingAs($driver)->get(route('driver.deliveries.index'))->assertOk();
        $this->actingAs($driver)->get(route('driver.deliveries.show', $order))->assertOk();
    }

    public function test_forbidden_page_renders(): void
    {
        $driver = $this->user(UserRole::Driver);

        $this->actingAs($driver)->get(route('admin.users.index'))->assertForbidden();
    }
}
