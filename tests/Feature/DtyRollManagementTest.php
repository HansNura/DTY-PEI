<?php

namespace Tests\Feature;

use App\Enums\RollStatus;
use App\Enums\UserRole;
use App\Models\DtyRoll;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DtyRollManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_warehouse_user_can_create_a_roll_with_a_unique_qr_token_and_print_label(): void
    {
        $warehouseUser = User::factory()->role(UserRole::AdminGudang)->create();

        $response = $this->actingAs($warehouseUser)->post(route('warehouse.rolls.store'), [
            'denier' => 150,
            'filamen' => 48,
            'berat_gram' => '1250.50',
            'nomor_lot' => 'LOT-2026-001',
        ]);

        $roll = DtyRoll::firstOrFail();

        $response->assertRedirect(route('warehouse.rolls.label', $roll));
        $this->assertSame(30, strlen($roll->kode_rol));
        $this->assertMatchesRegularExpression('/^\d{5}0079\d{8}$/', $roll->nomor_seri);
        $this->assertSame(64, strlen($roll->qr_token));
        $this->assertSame(RollStatus::InStock, $roll->status);
        $this->assertSame($warehouseUser->id, $roll->created_by);
        $this->assertSame('A', $roll->bobbin_weight_grade);

        $this->actingAs($warehouseUser)
            ->get(route('warehouse.rolls.label', $roll))
            ->assertOk()
            ->assertSee('data-qr-code', false)
            ->assertSee('data-qr-token="'.$roll->nomor_seri.'"', false)
            ->assertSee($roll->nomor_seri)
            ->assertSee('DTY 150/48 NI')
            ->assertSee('Merge / Lot');
    }

    public function test_warehouse_user_can_update_and_cancel_only_available_rolls(): void
    {
        $warehouseUser = User::factory()->role(UserRole::AdminGudang)->create();
        $roll = DtyRoll::factory()->create([
            'created_by' => $warehouseUser->id,
            'status' => RollStatus::InStock,
        ]);

        $this->actingAs($warehouseUser)
            ->put(route('warehouse.rolls.update', $roll), [
                'denier' => 300,
                'filamen' => 72,
                'berat_gram' => '900.00',
                'nomor_lot' => 'LOT-UPDATED',
            ])
            ->assertRedirect(route('warehouse.rolls.index'));

        $this->assertSame('LOT-UPDATED', $roll->fresh()->nomor_lot);
        $this->assertSame($roll->qr_token, $roll->fresh()->qr_token);

        $this->actingAs($warehouseUser)
            ->patch(route('warehouse.rolls.cancel', $roll))
            ->assertRedirect();

        $this->assertSame(RollStatus::Cancelled, $roll->fresh()->status);
        $this->assertDatabaseHas('dty_rolls', ['id' => $roll->id]);
    }

    public function test_allocated_roll_cannot_be_edited_or_cancelled(): void
    {
        $warehouseUser = User::factory()->role(UserRole::AdminGudang)->create();
        $roll = DtyRoll::factory()->create([
            'created_by' => $warehouseUser->id,
            'status' => RollStatus::Allocated,
        ]);

        $this->actingAs($warehouseUser)->get(route('warehouse.rolls.edit', $roll))->assertForbidden();
        $this->actingAs($warehouseUser)->patch(route('warehouse.rolls.cancel', $roll))->assertForbidden();
    }

    public function test_invalid_roll_specification_is_rejected(): void
    {
        $warehouseUser = User::factory()->role(UserRole::AdminGudang)->create();

        $this->actingAs($warehouseUser)
            ->post(route('warehouse.rolls.store'), [
                'denier' => 0,
                'filamen' => 48,
                'berat_gram' => '0',
                'nomor_lot' => '',
            ])
            ->assertSessionHasErrors(['denier', 'berat_gram', 'nomor_lot']);
    }

    public function test_roll_index_shows_summary_and_filters(): void
    {
        $warehouseUser = User::factory()->role(UserRole::AdminGudang)->create();
        $available = DtyRoll::factory()->create([
            'status' => RollStatus::InStock,
            'nomor_lot' => 'LOT-AVAILABLE',
            'berat_gram' => 2500,
        ]);
        $allocated = DtyRoll::factory()->create([
            'status' => RollStatus::Allocated,
            'nomor_lot' => 'LOT-ALLOCATED',
            'berat_gram' => 1000,
        ]);

        $this->actingAs($warehouseUser)
            ->get(route('warehouse.rolls.index'))
            ->assertOk()
            ->assertSee('Bobbin tersedia')
            ->assertSee('Dialokasikan')
            ->assertSee('Semua')
            ->assertViewHas('summary', fn (array $summary) => $summary['total'] === 2
                && $summary['in_stock'] === 1
                && $summary['allocated'] === 1
                && (float) $summary['available_weight_kg'] === 2.5)
            ->assertSee($available->kode_rol)
            ->assertSee($allocated->kode_rol);

        $this->actingAs($warehouseUser)
            ->get(route('warehouse.rolls.index', ['q' => 'LOT-AVAILABLE']))
            ->assertOk()
            ->assertSee($available->kode_rol)
            ->assertDontSee($allocated->kode_rol);

        $this->actingAs($warehouseUser)
            ->get(route('warehouse.rolls.index', ['status' => RollStatus::Allocated->value]))
            ->assertOk()
            ->assertSee($allocated->kode_rol)
            ->assertDontSee($available->kode_rol);
    }

    public function test_roll_form_has_placeholders(): void
    {
        $warehouseUser = User::factory()->role(UserRole::AdminGudang)->create();

        $this->actingAs($warehouseUser)
            ->get(route('warehouse.rolls.create'))
            ->assertOk()
            ->assertSee('Contoh: 150')
            ->assertSee('Contoh: 48')
            ->assertSee('Contoh: 5485')
            ->assertSee('Contoh: 4D9164')
            ->assertSee('Grade bobot otomatis')
            ->assertSee('Rentang bobot')
            ->assertSee('5.350–5.600 g')
            ->assertSee('Target penuh');
    }

    public function test_only_warehouse_users_can_access_roll_management(): void
    {
        $logisticsUser = User::factory()->role(UserRole::Logistik)->create();

        $this->actingAs($logisticsUser)->get(route('warehouse.rolls.index'))->assertForbidden();
    }
}
