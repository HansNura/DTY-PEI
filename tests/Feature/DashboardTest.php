<?php

namespace Tests\Feature;

use App\Enums\DeliveryOrderStatus;
use App\Enums\RollStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\DtyCarton;
use App\Models\DtyRoll;
use App\Models\Fleet;
use App\Models\ProductionBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function user(UserRole $role): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    public function test_warehouse_dashboard_shows_batch_and_anomaly_summary(): void
    {
        $warehouse = $this->user(UserRole::AdminGudang);
        ProductionBatch::factory()->create(['is_active' => true, 'created_by' => $warehouse->id]);
        ProductionBatch::factory()->create(['is_active' => false, 'created_by' => $warehouse->id]);
        DtyRoll::factory()->create([
            'status' => RollStatus::Overweight,
            'created_by' => $warehouse->id,
        ]);

        $this->actingAs($warehouse)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('batch aktif')
            ->assertSee('bobbin perlu tinjau')
            ->assertSee('1/2')
            ->assertSee('1 bobbin berstatus overweight atau waste');
    }

    public function test_logistics_dashboard_shows_box_progress_and_completion_counts(): void
    {
        $logistics = $this->user(UserRole::Logistik);
        $driver = $this->user(UserRole::Driver);
        $customer = Customer::factory()->create();
        $fleet = Fleet::factory()->create(['driver_id' => $driver->id]);
        $carton = DtyCarton::factory()->create(['created_by' => $logistics->id]);
        $secondCarton = DtyCarton::factory()->create(['created_by' => $logistics->id]);
        $roll = DtyRoll::factory()->create([
            'dty_carton_id' => $carton->id,
            'status' => RollStatus::Loaded,
            'created_by' => $logistics->id,
        ]);
        $secondRoll = DtyRoll::factory()->create([
            'dty_carton_id' => $secondCarton->id,
            'status' => RollStatus::Loaded,
            'created_by' => $logistics->id,
        ]);
        $order = DeliveryOrder::factory()->create([
            'customer_id' => $customer->id,
            'fleet_id' => $fleet->id,
            'driver_id' => $driver->id,
            'created_by' => $logistics->id,
            'status' => DeliveryOrderStatus::Loading,
        ]);
        foreach ([$roll, $secondRoll] as $itemRoll) {
            DeliveryOrderItem::create([
                'delivery_order_id' => $order->id,
                'dty_roll_id' => $itemRoll->id,
                'berat_kg' => 5.48,
            ]);
        }
        DeliveryOrder::factory()->create([
            'customer_id' => $customer->id,
            'fleet_id' => $fleet->id,
            'driver_id' => $driver->id,
            'created_by' => $logistics->id,
            'status' => DeliveryOrderStatus::Received,
        ]);

        $this->actingAs($logistics)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('2/2 kardus')
            ->assertSee('Diterima 1')
            ->assertSee('Dibatalkan 0');
    }

    public function test_driver_dashboard_highlights_arrived_trip_waiting_for_pod(): void
    {
        $driver = $this->user(UserRole::Driver);
        $customer = Customer::factory()->create();
        $fleet = Fleet::factory()->create(['driver_id' => $driver->id]);
        DeliveryOrder::factory()->create([
            'customer_id' => $customer->id,
            'fleet_id' => $fleet->id,
            'driver_id' => $driver->id,
            'status' => DeliveryOrderStatus::Arrived,
        ]);

        $this->actingAs($driver)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Menunggu konfirmasi penerimaan')
            ->assertSee('Konfirmasi penerimaan')
            ->assertSee('Perlu POD')
            ->assertSee('Selesai');
    }
}
