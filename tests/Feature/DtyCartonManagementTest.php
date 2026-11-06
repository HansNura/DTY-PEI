<?php

namespace Tests\Feature;

use App\Enums\RollStatus;
use App\Enums\UserRole;
use App\Models\DtyCarton;
use App\Models\DtyRoll;
use App\Models\ProductionBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DtyCartonManagementTest extends TestCase
{
    use RefreshDatabase;

    private static int $serial = 20217;

    private function warehouseUser(): User
    {
        return User::factory()->role(UserRole::AdminGudang)->create();
    }

    private function batch(string $merge = '4D9164'): ProductionBatch
    {
        return ProductionBatch::factory()->create([
            'nomor_merge' => $merge,
            'is_active' => true,
        ]);
    }

    private function readyRoll(
        ProductionBatch $batch,
        float $weight = 5485,
        ?string $grade = 'AX',
        RollStatus $status = RollStatus::ReadyToPack,
    ): DtyRoll {
        return DtyRoll::factory()->create([
            'production_batch_id' => $batch->id,
            'nomor_seri' => str_pad((string) self::$serial++, 5, '0', STR_PAD_LEFT).'0079'.'20261103',
            'grade' => $grade,
            'denier' => $batch->denier,
            'filamen' => $batch->filamen,
            'berat_gram' => $weight,
            'nomor_lot' => $batch->nomor_merge,
            'status' => $status,
            'created_by' => User::factory(),
        ]);
    }

    private function scan(User $user, DtyRoll $roll)
    {
        return $this->actingAs($user)->postJson(route('warehouse.packing.scan'), [
            'token' => $roll->nomor_seri,
        ]);
    }

    public function test_packing_six_bobbins_creates_carton_with_aggregated_data(): void
    {
        $user = $this->warehouseUser();
        $batch = $this->batch();
        $rolls = collect(range(1, 6))->map(fn () => $this->readyRoll($batch));

        foreach ($rolls as $roll) {
            $this->scan($user, $roll)->assertOk()->assertJson(['success' => true]);
        }

        $this->actingAs($user)
            ->post(route('warehouse.packing.store'), [
                'gross_weight_kg' => '35.56',
                'pallet_number' => 'adp063296',
            ])
            ->assertRedirect();

        $carton = DtyCarton::firstOrFail();

        $this->assertMatchesRegularExpression('/^ADMG\d{8}$/', $carton->nomor_hu);
        $this->assertSame($batch->nomor_merge, $carton->nomor_merge);
        $this->assertSame('AX', $carton->grade);
        $this->assertSame('ADP063296', $carton->pallet_number);
        $this->assertSame(6, $carton->unit_bobbins);
        $this->assertSame(32.91, (float) $carton->net_weight_kg);
        $this->assertSame(35.56, (float) $carton->gross_weight_kg);
        $this->assertSame(now()->toDateString(), $carton->production_date->toDateString());
        $this->assertCount(6, $carton->rolls);
        $this->assertTrue($carton->rolls->every(
            fn (DtyRoll $roll): bool => $roll->status === RollStatus::InStock && $roll->dty_carton_id === $carton->id
        ));

        $this->actingAs($user)
            ->get(route('warehouse.cartons.label', $carton))
            ->assertOk()
            ->assertSee($carton->nomor_hu)
            ->assertSee($batch->nomor_merge)
            ->assertSee('AX')
            ->assertSee($rolls->first()->nomor_seri);
    }

    public function test_packing_rejects_bobbins_from_a_different_merge(): void
    {
        $user = $this->warehouseUser();
        $first = $this->readyRoll($this->batch('4D9164'));
        $other = $this->readyRoll($this->batch('4D9165'));

        $this->scan($user, $first)->assertOk();
        $this->scan($user, $other)
            ->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonPath('summary.count', 1);
    }

    public function test_packing_rejects_a_different_bobbin_grade(): void
    {
        $user = $this->warehouseUser();
        $batch = $this->batch();
        $ax = $this->readyRoll($batch, 5485, 'AX');
        $ae = $this->readyRoll($batch, 5250, 'AE');

        $this->scan($user, $ax)->assertOk();
        $this->scan($user, $ae)
            ->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonPath('summary.count', 1);
    }

    public function test_packing_rejects_overweight_and_waste_bobbins(): void
    {
        $user = $this->warehouseUser();
        $batch = $this->batch();
        $overweight = $this->readyRoll($batch, 5700, 'OW', RollStatus::Overweight);
        $waste = $this->readyRoll($batch, 450, 'WASTE', RollStatus::Waste);

        $this->scan($user, $overweight)
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('summary.count', 0);

        $this->scan($user, $waste)
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('summary.count', 0);
    }

    public function test_packing_rejects_bobbin_that_is_already_packed(): void
    {
        $user = $this->warehouseUser();
        $carton = DtyCarton::factory()->create();
        $roll = $this->readyRoll($this->batch());
        $roll->update(['dty_carton_id' => $carton->id, 'status' => RollStatus::InStock]);

        $this->scan($user, $roll)
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_packing_requires_exactly_six_bobbins(): void
    {
        $user = $this->warehouseUser();
        $batch = $this->batch();

        foreach (range(1, 5) as $index) {
            $this->scan($user, $this->readyRoll($batch))->assertOk();
        }

        $this->actingAs($user)
            ->post(route('warehouse.packing.store'), ['gross_weight_kg' => '35.56'])
            ->assertSessionHasErrors('packing');

        $this->assertDatabaseCount('dty_cartons', 0);
    }

    public function test_ax_six_bobbin_gross_must_be_within_tolerance(): void
    {
        $user = $this->warehouseUser();
        $batch = $this->batch();

        foreach (range(1, 6) as $index) {
            $this->scan($user, $this->readyRoll($batch))->assertOk();
        }

        $this->actingAs($user)
            ->post(route('warehouse.packing.store'), ['gross_weight_kg' => '37.00'])
            ->assertSessionHasErrors('gross_weight_kg');

        $this->assertDatabaseCount('dty_cartons', 0);

        $this->actingAs($user)
            ->post(route('warehouse.packing.store'), ['gross_weight_kg' => '35.56'])
            ->assertRedirect();

        $this->assertDatabaseCount('dty_cartons', 1);
    }

    public function test_carton_index_shows_summary_filters_and_drawer(): void
    {
        $user = $this->warehouseUser();
        DtyCarton::factory()->create([
            'nomor_hu' => 'ADMG00054918',
            'nomor_merge' => '4D9164',
            'grade' => 'WE',
            'production_date' => '2026-11-04',
            'net_weight_kg' => 32.91,
            'gross_weight_kg' => 35.56,
        ]);
        DtyCarton::factory()->create([
            'nomor_hu' => 'ADMG00055000',
            'nomor_merge' => '4D9165',
            'grade' => 'WA',
            'production_date' => '2026-10-15',
            'net_weight_kg' => 31.50,
            'gross_weight_kg' => 34.00,
        ]);

        $this->actingAs($user)
            ->get(route('warehouse.cartons.index'))
            ->assertOk()
            ->assertSee('Total kardus')
            ->assertSee('data-carton-drawer', false)
            ->assertSee('Ringkasan kardus')
            ->assertViewHas('summary', fn (array $summary) => $summary['cartons'] === 2
                && $summary['bobbins'] === 12
                && abs($summary['net_kg'] - 64.41) < 0.001);

        $this->actingAs($user)
            ->get(route('warehouse.cartons.index', ['grade' => 'WA']))
            ->assertOk()
            ->assertSee('ADMG00055000')
            ->assertDontSee('ADMG00054918');

        $this->actingAs($user)
            ->get(route('warehouse.cartons.index', ['weight_grade' => 'AE']))
            ->assertOk()
            ->assertSee('ADMG00055000')
            ->assertDontSee('ADMG00054918');

        $this->actingAs($user)
            ->get(route('warehouse.cartons.index', ['month' => '2026-10']))
            ->assertOk()
            ->assertSee('ADMG00055000')
            ->assertDontSee('ADMG00054918');
    }

    public function test_edit_packing_replaces_a_bobbin_and_recalculates_the_carton(): void
    {
        $user = $this->warehouseUser();
        $batch = $this->batch();
        $rolls = collect(range(1, 6))->map(fn () => $this->readyRoll($batch));

        foreach ($rolls as $roll) {
            $this->scan($user, $roll)->assertOk();
        }

        $this->actingAs($user)->post(route('warehouse.packing.store'), [
            'gross_weight_kg' => '35.56',
        ])->assertRedirect();

        $carton = DtyCarton::firstOrFail();
        $oldRoll = $carton->rolls->firstOrFail();
        $replacement = $this->readyRoll($batch, 5400, 'AX');

        $this->actingAs($user)
            ->get(route('warehouse.cartons.edit', $carton))
            ->assertOk()
            ->assertSee('Mode edit isi kardus')
            ->assertSee('Kembalikan isi awal')
            ->assertViewHas('summary', fn (array $summary): bool => $summary['count'] === 6);

        $this->actingAs($user)
            ->deleteJson(route('warehouse.cartons.packing.remove', [$carton, $oldRoll]))
            ->assertOk()
            ->assertJsonPath('summary.count', 5);

        $this->actingAs($user)
            ->postJson(route('warehouse.cartons.packing.scan', $carton), [
                'token' => $replacement->nomor_seri,
            ])
            ->assertOk()
            ->assertJsonPath('summary.count', 6);

        $this->actingAs($user)
            ->put(route('warehouse.cartons.packing.update', $carton), [
                'gross_weight_kg' => '35.56',
            ])
            ->assertRedirect();

        $carton->refresh();
        $this->assertSame(6, $carton->unit_bobbins);
        $this->assertSame(32.83, (float) $carton->net_weight_kg);
        $this->assertSame(35.56, (float) $carton->gross_weight_kg);

        $this->assertSame(RollStatus::ReadyToPack, $oldRoll->fresh()->status);
        $this->assertNull($oldRoll->fresh()->dty_carton_id);
        $this->assertSame(RollStatus::InStock, $replacement->fresh()->status);
        $this->assertSame($carton->id, $replacement->fresh()->dty_carton_id);
        $this->assertSame(6, $carton->fresh()->rolls()->count());
    }

    public function test_edit_packing_rejects_a_replacement_with_a_different_grade(): void
    {
        $user = $this->warehouseUser();
        $batch = $this->batch();
        $rolls = collect(range(1, 6))->map(fn () => $this->readyRoll($batch));

        foreach ($rolls as $roll) {
            $this->scan($user, $roll)->assertOk();
        }

        $this->actingAs($user)->post(route('warehouse.packing.store'), [
            'gross_weight_kg' => '35.56',
        ]);

        $carton = DtyCarton::firstOrFail();
        $oldRoll = $carton->rolls->firstOrFail();
        $wrongGrade = $this->readyRoll($batch, 5250, 'AE');

        $this->actingAs($user)->get(route('warehouse.cartons.edit', $carton))->assertOk();
        $this->actingAs($user)
            ->deleteJson(route('warehouse.cartons.packing.remove', [$carton, $oldRoll]))
            ->assertOk();

        $this->actingAs($user)
            ->postJson(route('warehouse.cartons.packing.scan', $carton), [
                'token' => $wrongGrade->nomor_seri,
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_packed_carton_edit_only_updates_gross_pallet_and_notes(): void
    {
        $user = $this->warehouseUser();
        $carton = DtyCarton::factory()->create([
            'net_weight_kg' => 32.91,
            'gross_weight_kg' => 35.56,
        ]);

        $this->actingAs($user)
            ->put(route('warehouse.cartons.update', $carton), [
                'gross_weight_kg' => '36.00',
                'pallet_number' => 'adp999999',
                'catatan' => 'Koreksi timbangan.',
                'net_weight_kg' => '1.00',
                'unit_bobbins' => 1,
            ])
            ->assertRedirect(route('warehouse.cartons.index'));

        $carton->refresh();
        $this->assertSame(36.0, (float) $carton->gross_weight_kg);
        $this->assertSame('ADP999999', $carton->pallet_number);
        $this->assertSame('Koreksi timbangan.', $carton->catatan);
        $this->assertSame(32.91, (float) $carton->net_weight_kg);
        $this->assertSame(6, $carton->unit_bobbins);
    }

    public function test_gross_must_be_greater_than_net_on_carton_edit(): void
    {
        $user = $this->warehouseUser();
        $carton = DtyCarton::factory()->create([
            'net_weight_kg' => 32.91,
            'gross_weight_kg' => 35.56,
        ]);

        $this->actingAs($user)
            ->put(route('warehouse.cartons.update', $carton), [
                'gross_weight_kg' => '30.00',
            ])
            ->assertSessionHasErrors('gross_weight_kg');
    }

    public function test_carton_label_renders_identity_and_specification(): void
    {
        $user = $this->warehouseUser();
        $batch = $this->batch();
        $rolls = collect(range(1, 6))->map(fn () => $this->readyRoll($batch));

        foreach ($rolls as $roll) {
            $this->scan($user, $roll)->assertOk();
        }

        $this->actingAs($user)->post(route('warehouse.packing.store'), [
            'gross_weight_kg' => '35.56',
            'pallet_number' => 'ADP063296',
        ]);

        $carton = DtyCarton::firstOrFail();

        $this->actingAs($user)
            ->get(route('warehouse.cartons.label', $carton))
            ->assertOk()
            ->assertSee($carton->nomor_hu)
            ->assertSee('4D9164')
            ->assertSee('150/48 NI Z')
            ->assertSee('35.56')
            ->assertSee('32.91')
            ->assertSee('Grade bobot')
            ->assertSee('AX');
    }

    public function test_allocated_carton_cannot_be_edited(): void
    {
        $user = $this->warehouseUser();
        $carton = DtyCarton::factory()->create();
        DtyRoll::factory()->create([
            'dty_carton_id' => $carton->id,
            'status' => RollStatus::Allocated,
        ]);

        $this->actingAs($user)->get(route('warehouse.cartons.edit', $carton))->assertForbidden();
        $this->actingAs($user)->put(route('warehouse.cartons.update', $carton), ['gross_weight_kg' => '36.00'])->assertForbidden();
    }

    public function test_only_warehouse_users_can_access_carton_management(): void
    {
        $carton = DtyCarton::factory()->create();

        foreach ([UserRole::Logistik, UserRole::Driver, UserRole::Admin] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('warehouse.cartons.index'))
                ->assertForbidden();
        }

        $this->actingAs($this->warehouseUser())
            ->get(route('warehouse.cartons.index'))
            ->assertOk()
            ->assertSee($carton->nomor_hu);
    }
}
