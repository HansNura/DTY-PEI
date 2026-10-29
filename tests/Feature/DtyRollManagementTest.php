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
        $this->assertSame(64, strlen($roll->qr_token));
        $this->assertSame(RollStatus::InStock, $roll->status);
        $this->assertSame($warehouseUser->id, $roll->created_by);

        $this->actingAs($warehouseUser)
            ->get(route('warehouse.rolls.label', $roll))
            ->assertOk()
            ->assertSee('data-qr-code', false)
            ->assertSee('data-qr-token="'.$roll->qr_token.'"', false)
            ->assertSee($roll->kode_rol);
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

    public function test_only_warehouse_users_can_access_roll_management(): void
    {
        $logisticsUser = User::factory()->role(UserRole::Logistik)->create();

        $this->actingAs($logisticsUser)->get(route('warehouse.rolls.index'))->assertForbidden();
    }
}
