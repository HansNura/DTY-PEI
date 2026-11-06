<?php

namespace Tests\Feature;

use App\Enums\RollStatus;
use App\Enums\UserRole;
use App\Models\DtyRoll;
use App\Models\ProductionBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DtyRollManagementTest extends TestCase
{
    use RefreshDatabase;

    private function warehouseUser(): User
    {
        return User::factory()->role(UserRole::AdminGudang)->create();
    }

    private function activeBatch(): ProductionBatch
    {
        return ProductionBatch::factory()->create([
            'nomor_merge' => '4D9164',
            'is_active' => true,
        ]);
    }

    public function test_active_batch_is_required_before_weighing_a_bobbin(): void
    {
        $this->actingAs($this->warehouseUser())
            ->post(route('warehouse.rolls.store'), [
                'berat_gram' => '5485',
                'nomor_seri' => '99054007920261103',
            ])
            ->assertSessionHasErrors('berat_gram');

        $this->assertDatabaseCount('dty_rolls', 0);
    }

    public function test_warehouse_user_can_weigh_a_bobbin_with_the_active_batch(): void
    {
        $warehouseUser = $this->warehouseUser();
        $batch = $this->activeBatch();

        $response = $this->actingAs($warehouseUser)->post(route('warehouse.rolls.store'), [
            'berat_gram' => '5485.00',
            'nomor_seri' => '99054007920261103',
        ]);

        $roll = DtyRoll::firstOrFail();

        $response->assertRedirect(route('warehouse.rolls.label', $roll));
        $this->assertSame($batch->id, $roll->production_batch_id);
        $this->assertSame('AX', $roll->grade);
        $this->assertSame(RollStatus::ReadyToPack, $roll->status);
        $this->assertSame('4D9164', $roll->nomor_lot);
        $this->assertSame('DTY 150/48 NI Z', $roll->specification);
        $this->assertSame('99054007920261103', $roll->nomor_seri);
        $this->assertMatchesRegularExpression('/^\d{17}$/', $roll->nomor_seri);
        $this->assertSame(64, strlen($roll->qr_token));

        $this->actingAs($warehouseUser)
            ->get(route('warehouse.rolls.label', $roll))
            ->assertOk()
            ->assertSee('data-qr-code', false)
            ->assertSee('data-qr-token="'.$roll->nomor_seri.'"', false)
            ->assertSee($roll->nomor_seri)
            ->assertSee('DTY 150/48 NI Z')
            ->assertSee('Grade bobot')
            ->assertSee('Merge / Lot');
    }

    public function test_weights_outside_the_standard_are_classified_as_anomalies(): void
    {
        $warehouseUser = $this->warehouseUser();
        $this->activeBatch();

        $this->actingAs($warehouseUser)
            ->post(route('warehouse.rolls.store'), [
                'berat_gram' => '5601',
                'nomor_seri' => '99055007920261103',
            ])
            ->assertRedirect();

        $overweight = DtyRoll::where('berat_gram', 5601)->firstOrFail();
        $this->assertSame(RollStatus::Overweight, $overweight->status);
        $this->assertSame('OW', $overweight->grade);

        $this->actingAs($warehouseUser)
            ->get(route('warehouse.rolls.label', $overweight))
            ->assertOk()
            ->assertSee('OVERWEIGHT / HOLD')
            ->assertSee('Grade OW');

        $this->actingAs($warehouseUser)
            ->post(route('warehouse.rolls.store'), [
                'berat_gram' => '499',
                'nomor_seri' => '99056007920261103',
            ])
            ->assertRedirect(route('warehouse.rolls.index'));

        $waste = DtyRoll::where('berat_gram', 499)->firstOrFail();
        $this->assertSame(RollStatus::Waste, $waste->status);
        $this->assertSame('WASTE', $waste->grade);
        $this->actingAs($warehouseUser)->get(route('warehouse.rolls.label', $waste))->assertForbidden();
    }

    public function test_anomaly_bobbin_can_be_reweighed_back_to_a_standard_grade(): void
    {
        $warehouseUser = $this->warehouseUser();
        $roll = DtyRoll::factory()->create([
            'status' => RollStatus::Waste,
            'grade' => 'WASTE',
            'berat_gram' => 450,
            'nomor_seri' => '20219007920261103',
            'created_by' => $warehouseUser->id,
        ]);

        $this->actingAs($warehouseUser)
            ->get(route('warehouse.rolls.edit', $roll))
            ->assertOk();

        $this->actingAs($warehouseUser)
            ->put(route('warehouse.rolls.update', $roll), [
                'berat_gram' => '5400',
                'nomor_seri' => $roll->nomor_seri,
            ])
            ->assertRedirect(route('warehouse.rolls.index'));

        $roll->refresh();
        $this->assertSame(RollStatus::ReadyToPack, $roll->status);
        $this->assertSame('AX', $roll->grade);
    }

    public function test_ready_to_pack_bobbin_can_be_reweighed_and_cancelled_but_allocated_cannot(): void
    {
        $warehouseUser = $this->warehouseUser();
        $batch = $this->activeBatch();

        $roll = DtyRoll::factory()->create([
            'production_batch_id' => $batch->id,
            'nomor_seri' => '20217007920261103',
            'status' => RollStatus::ReadyToPack,
            'grade' => 'AX',
            'berat_gram' => 5485,
            'created_by' => $warehouseUser->id,
        ]);

        $this->actingAs($warehouseUser)
            ->put(route('warehouse.rolls.update', $roll), [
                'berat_gram' => '5250.00',
                'nomor_seri' => '20217007920261103',
            ])
            ->assertRedirect(route('warehouse.rolls.index'));

        $this->assertSame(5250.0, (float) $roll->fresh()->berat_gram);
        $this->assertSame('AE', $roll->fresh()->grade);
        $this->assertSame('20217007920261103', $roll->fresh()->nomor_seri);

        $this->actingAs($warehouseUser)
            ->patch(route('warehouse.rolls.cancel', $roll))
            ->assertRedirect();

        $this->assertSame(RollStatus::Cancelled, $roll->fresh()->status);

        $allocated = DtyRoll::factory()->create([
            'production_batch_id' => $batch->id,
            'status' => RollStatus::Allocated,
            'created_by' => $warehouseUser->id,
        ]);

        $this->actingAs($warehouseUser)->get(route('warehouse.rolls.edit', $allocated))->assertForbidden();
        $this->actingAs($warehouseUser)->patch(route('warehouse.rolls.cancel', $allocated))->assertForbidden();
    }

    public function test_roll_index_shows_summary_and_filters(): void
    {
        $warehouseUser = $this->warehouseUser();
        $ready = DtyRoll::factory()->create([
            'status' => RollStatus::ReadyToPack,
            'nomor_seri' => '20217007920261103',
            'nomor_lot' => 'LOT-READY',
            'berat_gram' => 2500,
            'created_by' => $warehouseUser->id,
        ]);
        $inStock = DtyRoll::factory()->create([
            'status' => RollStatus::InStock,
            'nomor_seri' => '20218007920261103',
            'nomor_lot' => 'LOT-STOCK',
            'berat_gram' => 1000,
            'created_by' => $warehouseUser->id,
        ]);

        $this->actingAs($warehouseUser)
            ->get(route('warehouse.rolls.index'))
            ->assertOk()
            ->assertSee('Siap packing')
            ->assertSee('Tersedia')
            ->assertViewHas('summary', fn (array $summary) => $summary['total'] === 2
                && $summary['ready_to_pack'] === 1
                && $summary['in_stock'] === 1
                && (float) $summary['available_weight_kg'] === 3.5)
            ->assertSee('20217007920261103')
            ->assertSee('20218007920261103');

        $this->actingAs($warehouseUser)
            ->get(route('warehouse.rolls.index', ['status' => RollStatus::ReadyToPack->value]))
            ->assertOk()
            ->assertSee('20217007920261103')
            ->assertDontSee('20218007920261103');

        $this->actingAs($warehouseUser)
            ->get(route('warehouse.rolls.index', ['q' => 'LOT-READY']))
            ->assertOk()
            ->assertSee('20217007920261103')
            ->assertDontSee('20218007920261103');
    }

    public function test_edit_weight_page_shows_the_bobbin_serial(): void
    {
        $warehouseUser = $this->warehouseUser();
        $roll = DtyRoll::factory()->create([
            'nomor_seri' => '20233007920261103',
            'status' => RollStatus::ReadyToPack,
            'created_by' => $warehouseUser->id,
        ]);

        $this->actingAs($warehouseUser)
            ->get(route('warehouse.rolls.edit', $roll))
            ->assertOk()
            ->assertSee('20233007920261103')
            ->assertSee('Nomor seri')
            ->assertDontSee($roll->kode_rol);
    }

    public function test_weighing_form_has_manual_serial_and_component_explanation(): void
    {
        $warehouseUser = $this->warehouseUser();
        $this->activeBatch();

        $this->actingAs($warehouseUser)
            ->get(route('warehouse.rolls.create'))
            ->assertOk()
            ->assertSee('Identitas bobbin')
            ->assertSee('17 digit · manual')
            ->assertSee('Nomor seri')
            ->assertSee('name="nomor_seri"', false)
            ->assertSee('99054007920261103')
            ->assertSee('Kode area / lini produksi / modul DTY')
            ->assertSee('Nomor mesin / posisi spindel')
            ->assertSee('Nomor siklus doffing / section posisi')
            ->assertSee('Kode shift kerja / sisi mesin')
            ->assertSee('Tanggal produksi doffing')
            ->assertDontSee('Kode rol')
            ->assertDontSee('Pratinjau otomatis');
    }

    public function test_manual_serial_is_used_when_saving_the_bobbin(): void
    {
        $warehouseUser = $this->warehouseUser();
        $this->activeBatch();

        $this->actingAs($warehouseUser)
            ->post(route('warehouse.rolls.store'), [
                'berat_gram' => '5485',
                'nomor_seri' => '99099007920261103',
            ])
            ->assertRedirect();

        $roll = DtyRoll::firstOrFail();
        $this->assertSame('99099007920261103', $roll->nomor_seri);
    }

    public function test_nomor_seri_must_be_17_digits(): void
    {
        $warehouseUser = $this->warehouseUser();
        $this->activeBatch();

        $this->actingAs($warehouseUser)
            ->post(route('warehouse.rolls.store'), [
                'berat_gram' => '5485',
                'nomor_seri' => '9905400792026110',
            ])
            ->assertSessionHasErrors('nomor_seri');

        $this->assertDatabaseCount('dty_rolls', 0);
    }

    public function test_nomor_seri_must_be_unique(): void
    {
        $warehouseUser = $this->warehouseUser();
        $this->activeBatch();
        DtyRoll::factory()->create([
            'nomor_seri' => '99054007920261103',
            'created_by' => $warehouseUser->id,
        ]);

        $this->actingAs($warehouseUser)
            ->post(route('warehouse.rolls.store'), [
                'berat_gram' => '5485',
                'nomor_seri' => '99054007920261103',
            ])
            ->assertSessionHasErrors('nomor_seri');
    }

    public function test_nomor_seri_can_be_corrected_when_editing_weight(): void
    {
        $warehouseUser = $this->warehouseUser();
        $roll = DtyRoll::factory()->create([
            'nomor_seri' => '20233007920261103',
            'status' => RollStatus::ReadyToPack,
            'created_by' => $warehouseUser->id,
        ]);

        $this->actingAs($warehouseUser)
            ->put(route('warehouse.rolls.update', $roll), [
                'berat_gram' => '5485',
                'nomor_seri' => '99054007920261103',
            ])
            ->assertRedirect(route('warehouse.rolls.index'));

        $this->assertSame('99054007920261103', $roll->fresh()->nomor_seri);
    }

    public function test_weighing_form_shows_active_batch_and_grade_table(): void
    {
        $warehouseUser = $this->warehouseUser();
        $this->activeBatch();

        $this->actingAs($warehouseUser)
            ->get(route('warehouse.rolls.create'))
            ->assertOk()
            ->assertSee('4D9164')
            ->assertSee('DTY 150/48 NI Z')
            ->assertSee('Grade bobot otomatis')
            ->assertSee('Rentang bobot')
            ->assertSee('Overweight / Hold')
            ->assertSee('Waste / Scrap')
            ->assertSee('Contoh: 5485')
            ->assertDontSee('name="denier"', false)
            ->assertDontSee('name="filamen"', false)
            ->assertDontSee('name="nomor_lot"', false);
    }

    public function test_weighing_form_explains_when_no_batch_is_active(): void
    {
        $this->actingAs($this->warehouseUser())
            ->get(route('warehouse.rolls.create'))
            ->assertOk()
            ->assertSee('Belum ada batch produksi aktif');
    }

    public function test_only_warehouse_users_can_access_roll_management(): void
    {
        $logisticsUser = User::factory()->role(UserRole::Logistik)->create();

        $this->actingAs($logisticsUser)->get(route('warehouse.rolls.index'))->assertForbidden();
    }
}
