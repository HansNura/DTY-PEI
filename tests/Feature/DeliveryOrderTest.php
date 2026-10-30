<?php

namespace Tests\Feature;

use App\Enums\DeliveryOrderStatus;
use App\Enums\RollStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\DtyRoll;
use App\Models\Fleet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryOrderTest extends TestCase
{
    use RefreshDatabase;
    private function logistikUser(): User
    {
        return User::factory()->create(['role' => UserRole::Logistik]);
    }

    private function warehouseUser(): User
    {
        return User::factory()->create(['role' => UserRole::AdminGudang]);
    }

    private function makeRolls(int $count = 2, ?User $creator = null): \Illuminate\Database\Eloquent\Collection
    {
        $creator ??= $this->warehouseUser();

        return DtyRoll::factory($count)->create([
            'status' => RollStatus::InStock,
            'created_by' => $creator->id,
        ]);
    }

    private function basePayload(Customer $customer, Fleet $fleet, User $driver, array $rollIds): array
    {
        return [
            'customer_id' => $customer->id,
            'fleet_id' => $fleet->id,
            'driver_id' => $driver->id,
            'tanggal' => now()->toDateString(),
            'catatan' => 'Pengiriman batch test',
            'rolls' => $rollIds,
        ];
    }

    public function test_logistics_user_can_create_delivery_order_and_rolls_become_allocated(): void
    {
        $user = $this->logistikUser();
        $customer = Customer::factory()->create(['is_active' => true]);
        $fleet = Fleet::factory()->create(['is_active' => true]);
        $driver = User::factory()->create(['role' => UserRole::Driver, 'is_active' => true]);
        $rolls = $this->makeRolls(3);

        $response = $this->actingAs($user)->post(
            route('logistics.orders.store'),
            $this->basePayload($customer, $fleet, $driver, $rolls->pluck('id')->toArray())
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('delivery_orders', [
            'customer_id' => $customer->id,
            'fleet_id' => $fleet->id,
            'driver_id' => $driver->id,
            'status' => DeliveryOrderStatus::Issued->value,
            'created_by' => $user->id,
        ]);
        $this->assertDatabaseCount('delivery_order_items', 3);
        foreach ($rolls as $roll) {
            $this->assertDatabaseHas('dty_rolls', ['id' => $roll->id, 'status' => RollStatus::Allocated->value]);
        }
    }

    public function test_nomor_do_is_auto_generated_and_unique(): void
    {
        $user = $this->logistikUser();
        $customer = Customer::factory()->create(['is_active' => true]);
        $fleet = Fleet::factory()->create(['is_active' => true]);
        $driver = User::factory()->create(['role' => UserRole::Driver, 'is_active' => true]);

        $rolls1 = $this->makeRolls(1);
        $rolls2 = $this->makeRolls(1);

        $this->actingAs($user)->post(route('logistics.orders.store'), $this->basePayload($customer, $fleet, $driver, $rolls1->pluck('id')->toArray()));
        $this->actingAs($user)->post(route('logistics.orders.store'), $this->basePayload($customer, $fleet, $driver, $rolls2->pluck('id')->toArray()));

        $this->assertSame(2, DeliveryOrder::count());
        $this->assertSame(2, DeliveryOrder::distinct('nomor_do')->count('nomor_do'));
    }

    public function test_allocated_roll_cannot_be_added_to_another_delivery_order(): void
    {
        $user = $this->logistikUser();
        $customer = Customer::factory()->create(['is_active' => true]);
        $fleet = Fleet::factory()->create(['is_active' => true]);
        $driver = User::factory()->create(['role' => UserRole::Driver, 'is_active' => true]);

        $roll = DtyRoll::factory()->create(['status' => RollStatus::InStock, 'created_by' => $this->warehouseUser()->id]);

        // First order
        $this->actingAs($user)->post(route('logistics.orders.store'), $this->basePayload($customer, $fleet, $driver, [$roll->id]));
        $this->assertSame(RollStatus::Allocated->value, $roll->fresh()->status->value);

        // Second order with same roll should fail
        $response = $this->actingAs($user)->post(route('logistics.orders.store'), $this->basePayload($customer, $fleet, $driver, [$roll->id]));
        $response->assertSessionHasErrors();
        $this->assertSame(1, DeliveryOrder::count());
    }

    public function test_cancel_delivery_order_returns_rolls_to_stock(): void
    {
        $user = $this->logistikUser();
        $customer = Customer::factory()->create(['is_active' => true]);
        $fleet = Fleet::factory()->create(['is_active' => true]);
        $driver = User::factory()->create(['role' => UserRole::Driver, 'is_active' => true]);
        $rolls = $this->makeRolls(2);

        $this->actingAs($user)->post(route('logistics.orders.store'), $this->basePayload($customer, $fleet, $driver, $rolls->pluck('id')->toArray()));

        $order = DeliveryOrder::first();

        $response = $this->actingAs($user)->patch(route('logistics.orders.cancel', $order));
        $response->assertRedirect(route('logistics.orders.show', $order));

        $this->assertSame(DeliveryOrderStatus::Cancelled->value, $order->fresh()->status->value);
        foreach ($rolls as $roll) {
            $this->assertSame(RollStatus::InStock->value, $roll->fresh()->status->value);
        }
    }

    public function test_only_logistics_user_can_access_delivery_orders(): void
    {
        $order = DeliveryOrder::factory()->create();

        $this->actingAs(User::factory()->create(['role' => UserRole::AdminGudang]))
            ->get(route('logistics.orders.index'))
            ->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => UserRole::Driver]))
            ->get(route('logistics.orders.index'))
            ->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))
            ->get(route('logistics.orders.index'))
            ->assertForbidden();

        $this->actingAs($this->logistikUser())
            ->get(route('logistics.orders.index'))
            ->assertOk();
    }

    public function test_rolls_field_is_required_and_must_exist(): void
    {
        $user = $this->logistikUser();
        $customer = Customer::factory()->create(['is_active' => true]);
        $fleet = Fleet::factory()->create(['is_active' => true]);
        $driver = User::factory()->create(['role' => UserRole::Driver, 'is_active' => true]);

        // No rolls
        $this->actingAs($user)->post(route('logistics.orders.store'), [
            'customer_id' => $customer->id,
            'fleet_id' => $fleet->id,
            'driver_id' => $driver->id,
            'tanggal' => now()->toDateString(),
            'rolls' => [],
        ])->assertSessionHasErrors('rolls');

        // Non-existent roll id
        $this->actingAs($user)->post(route('logistics.orders.store'), [
            'customer_id' => $customer->id,
            'fleet_id' => $fleet->id,
            'driver_id' => $driver->id,
            'tanggal' => now()->toDateString(),
            'rolls' => [99999],
        ])->assertSessionHasErrors('rolls.*');
    }
}
