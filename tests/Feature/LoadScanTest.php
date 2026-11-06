<?php

namespace Tests\Feature;

use App\Enums\DeliveryOrderStatus;
use App\Enums\LoadScanResult;
use App\Enums\RollStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\DtyCarton;
use App\Models\DtyRoll;
use App\Models\Fleet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class LoadScanTest extends TestCase
{
    use RefreshDatabase;

    private function warehouseUser(): User
    {
        return User::factory()->create(['role' => UserRole::AdminGudang]);
    }

    /**
     * @return array{0: DeliveryOrder, 1: Collection<int, DtyCarton>, 2: Collection<int, DtyRoll>}
     */
    private function makeOrderWithCartons(int $cartonCount = 1): array
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

        $warehouseUser = $this->warehouseUser();
        $cartons = collect();
        $rolls = collect();

        foreach (range(1, $cartonCount) as $cartonIndex) {
            $carton = DtyCarton::factory()->create([
                'nomor_hu' => 'ADMG-SCAN-'.str_pad((string) $cartonIndex, 3, '0', STR_PAD_LEFT),
                'nomor_merge' => '4D9164',
                'grade' => 'AX',
                'unit_bobbins' => 6,
                'created_by' => $warehouseUser->id,
            ]);

            $cartons->push($carton);

            foreach (range(1, 6) as $rollIndex) {
                $roll = DtyRoll::factory()->create([
                    'dty_carton_id' => $carton->id,
                    'status' => RollStatus::Allocated,
                    'denier' => 150,
                    'filamen' => 48,
                    'nomor_lot' => $carton->nomor_merge,
                    'created_by' => $warehouseUser->id,
                ]);

                $rolls->push($roll);

                DeliveryOrderItem::create([
                    'delivery_order_id' => $order->id,
                    'dty_roll_id' => $roll->id,
                    'berat_kg' => round($roll->berat_gram / 1000, 2),
                ]);
            }
        }

        return [$order, $cartons, $rolls];
    }

    public function test_valid_carton_scan_loads_all_bobbins_and_records_one_scan(): void
    {
        $checker = $this->warehouseUser();
        [$order, $cartons, $rolls] = $this->makeOrderWithCartons(1);
        $carton = $cartons->first();

        $response = $this->actingAs($checker)
            ->postJson(route('warehouse.scans.scan', $order), ['qr_token' => $carton->nomor_hu]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'result' => LoadScanResult::Valid->value,
                'loaded_box_count' => 1,
                'total_box' => 1,
            ]);

        $this->assertSame(6, $rolls->filter(fn (DtyRoll $roll) => $roll->fresh()->status === RollStatus::Loaded)->count());
        $this->assertDatabaseHas('load_scans', [
            'delivery_order_id' => $order->id,
            'dty_carton_id' => $carton->id,
            'result' => LoadScanResult::Valid->value,
            'scanned_by' => $checker->id,
        ]);
        $this->assertDatabaseCount('load_scans', 1);
        $this->assertSame(DeliveryOrderStatus::Loading->value, $order->fresh()->status->value);
    }

    public function test_carton_can_be_scanned_by_qr_token_or_manual_hu(): void
    {
        $checker = $this->warehouseUser();
        [$order, $cartons] = $this->makeOrderWithCartons(2);

        $this->actingAs($checker)
            ->postJson(route('warehouse.scans.scan', $order), ['qr_token' => $cartons[0]->qr_token])
            ->assertOk()
            ->assertJson(['success' => true, 'loaded_box_count' => 1]);

        $this->actingAs($checker)
            ->postJson(route('warehouse.scans.scan', $order), ['qr_token' => $cartons[1]->nomor_hu])
            ->assertOk()
            ->assertJson(['success' => true, 'loaded_box_count' => 2, 'is_complete' => true]);
    }

    public function test_scanning_a_bobbin_qr_is_rejected_as_wrong_unit(): void
    {
        $checker = $this->warehouseUser();
        [$order, , $rolls] = $this->makeOrderWithCartons(1);
        $roll = $rolls->first();

        $response = $this->actingAs($checker)
            ->postJson(route('warehouse.scans.scan', $order), ['qr_token' => $roll->qr_token]);

        $response->assertStatus(422)
            ->assertJson(['success' => false, 'result' => LoadScanResult::WrongUnit->value]);

        $this->assertSame(RollStatus::Allocated->value, $roll->fresh()->status->value);
        $this->assertDatabaseHas('load_scans', [
            'delivery_order_id' => $order->id,
            'dty_roll_id' => $roll->id,
            'result' => LoadScanResult::WrongUnit->value,
        ]);
    }

    public function test_carton_not_in_do_returns_wrong_do(): void
    {
        $checker = $this->warehouseUser();
        [$order] = $this->makeOrderWithCartons(1);
        $otherCarton = DtyCarton::factory()->create([
            'nomor_hu' => 'ADMG-LAIN-001',
            'nomor_merge' => '4D9999',
        ]);
        DtyRoll::factory(6)->create([
            'dty_carton_id' => $otherCarton->id,
            'status' => RollStatus::Allocated,
            'nomor_lot' => $otherCarton->nomor_merge,
        ]);

        $response = $this->actingAs($checker)
            ->postJson(route('warehouse.scans.scan', $order), ['qr_token' => $otherCarton->nomor_hu]);

        $response->assertStatus(422)
            ->assertJson(['success' => false, 'result' => LoadScanResult::WrongDo->value]);

        $this->assertDatabaseHas('load_scans', [
            'delivery_order_id' => $order->id,
            'dty_carton_id' => $otherCarton->id,
            'result' => LoadScanResult::WrongDo->value,
        ]);
    }

    public function test_scanning_already_loaded_carton_returns_duplicate(): void
    {
        $checker = $this->warehouseUser();
        [$order, $cartons] = $this->makeOrderWithCartons(1);
        $carton = $cartons->first();

        $this->actingAs($checker)
            ->postJson(route('warehouse.scans.scan', $order), ['qr_token' => $carton->nomor_hu])
            ->assertOk();

        $this->actingAs($checker)
            ->postJson(route('warehouse.scans.scan', $order), ['qr_token' => $carton->nomor_hu])
            ->assertStatus(422)
            ->assertJson(['success' => false, 'result' => LoadScanResult::Duplicate->value]);

        $this->assertDatabaseCount('load_scans', 2);
    }

    public function test_scanning_unknown_qr_returns_unknown_result(): void
    {
        $checker = $this->warehouseUser();
        [$order] = $this->makeOrderWithCartons(1);

        $response = $this->actingAs($checker)
            ->postJson(route('warehouse.scans.scan', $order), ['qr_token' => 'HU-TIDAK-ADA-XYZ']);

        $response->assertStatus(422)
            ->assertJson(['success' => false, 'result' => LoadScanResult::Unknown->value]);

        $this->assertDatabaseHas('load_scans', [
            'delivery_order_id' => $order->id,
            'dty_carton_id' => null,
            'dty_roll_id' => null,
            'result' => LoadScanResult::Unknown->value,
        ]);
    }

    public function test_only_warehouse_users_can_access_scanner(): void
    {
        [$order] = $this->makeOrderWithCartons(1);

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
        [$order] = $this->makeOrderWithCartons(1);

        $this->actingAs($checker)
            ->postJson(route('warehouse.scans.scan', $order), ['qr_token' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors('qr_token');
    }

    public function test_cancelled_order_cannot_be_scanned(): void
    {
        $checker = $this->warehouseUser();
        [$order, $cartons] = $this->makeOrderWithCartons(1);
        $carton = $cartons->first();

        $order->update(['status' => DeliveryOrderStatus::Cancelled]);

        $this->actingAs($checker)
            ->postJson(route('warehouse.scans.scan', $order), ['qr_token' => $carton->nomor_hu])
            ->assertStatus(422)
            ->assertJson(['success' => false, 'result' => LoadScanResult::InvalidOrder->value]);

        $this->assertDatabaseCount('load_scans', 0);
    }

    public function test_scanner_page_is_forbidden_for_closed_order(): void
    {
        [$order] = $this->makeOrderWithCartons(1);
        $order->update(['status' => DeliveryOrderStatus::Cancelled]);

        $this->actingAs($this->warehouseUser())
            ->get(route('warehouse.scans.scanner', $order))
            ->assertForbidden();
    }

    public function test_carton_with_unready_bobbin_is_rejected(): void
    {
        $checker = $this->warehouseUser();
        [$order, $cartons, $rolls] = $this->makeOrderWithCartons(1);
        $carton = $cartons->first();
        $rolls->first()->update(['status' => RollStatus::Cancelled]);

        $this->actingAs($checker)
            ->postJson(route('warehouse.scans.scan', $order), ['qr_token' => $carton->nomor_hu])
            ->assertStatus(422)
            ->assertJson(['success' => false, 'result' => LoadScanResult::WrongDo->value]);

        $this->assertDatabaseCount('load_scans', 1);
    }
}
