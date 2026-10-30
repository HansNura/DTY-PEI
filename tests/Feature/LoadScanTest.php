<?php

namespace Tests\Feature;

use App\Enums\DeliveryOrderStatus;
use App\Enums\LoadScanResult;
use App\Enums\RollStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\DtyRoll;
use App\Models\Fleet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoadScanTest extends TestCase
{
    use RefreshDatabase;
    private function warehouseUser(): User
    {
        return User::factory()->create(['role' => UserRole::AdminGudang]);
    }

    private function makeOrderWithRolls(int $count = 2): array
    {
        $driver = User::factory()->create(['role' => UserRole::Driver, 'is_active' => true]);
        $creator = User::factory()->create(['role' => UserRole::Logistik]);
        $customer = Customer::factory()->create(['is_active' => true]);
        $fleet = Fleet::factory()->create(['is_active' => true, 'driver_id' => $driver->id]);

        $order = DeliveryOrder::factory()->create([
            'customer_id' => $customer->id,
            'fleet_id' => $fleet->id,
            'driver_id' => $driver->id,
            'status' => DeliveryOrderStatus::Issued,
            'created_by' => $creator->id,
        ]);

        $warehouseUser = User::factory()->create(['role' => UserRole::AdminGudang]);
        $rolls = DtyRoll::factory($count)->create([
            'status' => RollStatus::Allocated,
            'created_by' => $warehouseUser->id,
        ]);

        foreach ($rolls as $roll) {
            DeliveryOrderItem::create([
                'delivery_order_id' => $order->id,
                'dty_roll_id' => $roll->id,
                'berat_kg' => round($roll->berat_gram / 1000, 2),
            ]);
        }

        return [$order, $rolls];
    }

    public function test_valid_qr_scan_marks_roll_as_loaded_and_records_scan(): void
    {
        $checker = $this->warehouseUser();
        [$order, $rolls] = $this->makeOrderWithRolls(2);
        $targetRoll = $rolls->first();

        $response = $this->actingAs($checker)
            ->postJson(route('warehouse.scans.scan', $order), ['qr_token' => $targetRoll->qr_token]);

        $response->assertOk()
            ->assertJson(['success' => true, 'result' => LoadScanResult::Valid->value]);

        $this->assertSame(RollStatus::Loaded->value, $targetRoll->fresh()->status->value);
        $this->assertDatabaseHas('load_scans', [
            'delivery_order_id' => $order->id,
            'dty_roll_id' => $targetRoll->id,
            'result' => LoadScanResult::Valid->value,
            'scanned_by' => $checker->id,
        ]);
        $this->assertSame(DeliveryOrderStatus::Loading->value, $order->fresh()->status->value);
    }

    public function test_scanning_roll_not_in_do_returns_wrong_do(): void
    {
        $checker = $this->warehouseUser();
        [$order] = $this->makeOrderWithRolls(1);

        $otherRoll = DtyRoll::factory()->create([
            'status' => RollStatus::InStock,
            'created_by' => $checker->id,
        ]);

        $response = $this->actingAs($checker)
            ->postJson(route('warehouse.scans.scan', $order), ['qr_token' => $otherRoll->qr_token]);

        $response->assertStatus(422)
            ->assertJson(['success' => false, 'result' => LoadScanResult::WrongDo->value]);

        $this->assertSame(RollStatus::InStock->value, $otherRoll->fresh()->status->value);
        $this->assertDatabaseHas('load_scans', [
            'delivery_order_id' => $order->id,
            'result' => LoadScanResult::WrongDo->value,
        ]);
    }

    public function test_scanning_already_loaded_roll_returns_duplicate(): void
    {
        $checker = $this->warehouseUser();
        [$order, $rolls] = $this->makeOrderWithRolls(2);
        $roll = $rolls->first();

        // First scan — valid
        $this->actingAs($checker)
            ->postJson(route('warehouse.scans.scan', $order), ['qr_token' => $roll->qr_token]);

        // Second scan of same roll — duplicate
        $response = $this->actingAs($checker)
            ->postJson(route('warehouse.scans.scan', $order), ['qr_token' => $roll->qr_token]);

        $response->assertStatus(422)
            ->assertJson(['success' => false, 'result' => LoadScanResult::Duplicate->value]);

        $this->assertDatabaseCount('load_scans', 2);
    }

    public function test_scanning_unknown_qr_returns_unknown_result(): void
    {
        $checker = $this->warehouseUser();
        [$order] = $this->makeOrderWithRolls(1);

        $response = $this->actingAs($checker)
            ->postJson(route('warehouse.scans.scan', $order), ['qr_token' => 'qr-tidak-ada-di-sistem-xyz']);

        $response->assertStatus(422)
            ->assertJson(['success' => false, 'result' => LoadScanResult::Unknown->value]);

        $this->assertDatabaseHas('load_scans', [
            'delivery_order_id' => $order->id,
            'dty_roll_id' => null,
            'result' => LoadScanResult::Unknown->value,
        ]);
    }

    public function test_only_warehouse_users_can_access_scanner(): void
    {
        [$order] = $this->makeOrderWithRolls(1);

        $this->actingAs(User::factory()->create(['role' => UserRole::Logistik]))
            ->get(route('warehouse.scans.index'))
            ->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => UserRole::Driver]))
            ->get(route('warehouse.scans.index'))
            ->assertForbidden();

        $this->actingAs($this->warehouseUser())
            ->get(route('warehouse.scans.index'))
            ->assertOk();
    }

    public function test_qr_token_is_required_to_scan(): void
    {
        $checker = $this->warehouseUser();
        [$order] = $this->makeOrderWithRolls(1);

        $this->actingAs($checker)
            ->postJson(route('warehouse.scans.scan', $order), ['qr_token' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors('qr_token');
    }
}
