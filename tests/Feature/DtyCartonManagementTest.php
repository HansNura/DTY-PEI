<?php

namespace Tests\Feature;

use App\Enums\RollStatus;
use App\Enums\UserRole;
use App\Models\DtyCarton;
use App\Models\DtyRoll;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DtyCartonManagementTest extends TestCase
{
    use RefreshDatabase;

    private function warehouseUser(): User
    {
        return User::factory()->create(['role' => UserRole::AdminGudang]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'nomor_hu' => 'admg00054918',
            'nomor_merge' => '4d9164',
            'grade' => 'we',
            'production_date' => '2026-11-04',
            'denier' => 150,
            'filamen' => 48,
            'intermingled' => '0',
            'twist_direction' => 'Z',
            'gross_weight_kg' => '35.56',
            'net_weight_kg' => '32.91',
            'unit_bobbins' => 6,
            'pallet_number' => 'adp063296',
        ], $overrides);
    }

    public function test_warehouse_user_can_create_carton_with_bobbins(): void
    {
        $user = $this->warehouseUser();

        $response = $this->actingAs($user)->post(route('warehouse.cartons.store'), $this->payload());

        $carton = DtyCarton::firstOrFail();
        $response->assertRedirect(route('warehouse.cartons.label', $carton));

        $this->assertSame('ADMG00054918', $carton->nomor_hu);
        $this->assertSame('4D9164', $carton->nomor_merge);
        $this->assertSame('WE', $carton->grade);
        $this->assertSame('150/48 NI Z', $carton->specification);
        $this->assertSame(6, $carton->rolls()->count());
        $this->assertSame(5485.0, (float) $carton->net_weight_per_bobbin_gram);

        foreach ($carton->rolls as $roll) {
            $this->assertSame(RollStatus::InStock, $roll->status);
            $this->assertSame('4D9164', $roll->nomor_lot);
            $this->assertSame($carton->id, $roll->dty_carton_id);
            $this->assertSame(5485.0, (float) $roll->berat_gram);
            $this->assertSame(64, strlen($roll->qr_token));
            $this->assertMatchesRegularExpression('/^\d{5}0079\d{8}$/', $roll->nomor_seri);
        }

        $this->assertSame(6, DtyRoll::distinct('qr_token')->count('qr_token'));
        $this->assertSame(6, DtyRoll::distinct('kode_rol')->count('kode_rol'));
        $this->assertSame(6, DtyRoll::distinct('nomor_seri')->count('nomor_seri'));
    }

    public function test_net_weight_cannot_exceed_gross_weight(): void
    {
        $this->actingAs($this->warehouseUser())
            ->post(route('warehouse.cartons.store'), $this->payload([
                'gross_weight_kg' => '20.00',
                'net_weight_kg' => '25.00',
            ]))
            ->assertSessionHasErrors('net_weight_kg');

        $this->assertDatabaseCount('dty_cartons', 0);
    }

    public function test_hu_number_must_be_unique(): void
    {
        $user = $this->warehouseUser();
        DtyCarton::factory()->create(['nomor_hu' => 'ADMG00054918']);

        $this->actingAs($user)
            ->post(route('warehouse.cartons.store'), $this->payload())
            ->assertSessionHasErrors('nomor_hu');
    }

    public function test_unit_bobbins_must_be_valid(): void
    {
        $this->actingAs($this->warehouseUser())
            ->post(route('warehouse.cartons.store'), $this->payload(['unit_bobbins' => 0]))
            ->assertSessionHasErrors('unit_bobbins');
    }

    public function test_update_recalculates_bobbin_weights_without_changing_count(): void
    {
        $user = $this->warehouseUser();
        $this->actingAs($user)->post(route('warehouse.cartons.store'), $this->payload());
        $carton = DtyCarton::firstOrFail();

        $this->actingAs($user)
            ->put(route('warehouse.cartons.update', $carton), $this->payload([
                'net_weight_kg' => '30.00',
                'gross_weight_kg' => '33.00',
                'grade' => 'WA',
            ]))
            ->assertRedirect(route('warehouse.cartons.index'));

        $this->assertSame('WA', $carton->fresh()->grade);
        $this->assertSame(5000.0, (float) $carton->fresh()->net_weight_per_bobbin_gram);

        foreach ($carton->rolls()->get() as $roll) {
            $this->assertSame(5000.0, (float) $roll->berat_gram);
        }
    }

    public function test_unit_bobbins_cannot_be_changed_after_creation(): void
    {
        $user = $this->warehouseUser();
        $this->actingAs($user)->post(route('warehouse.cartons.store'), $this->payload());
        $carton = DtyCarton::firstOrFail();

        $this->actingAs($user)
            ->put(route('warehouse.cartons.update', $carton), $this->payload(['unit_bobbins' => 5]))
            ->assertSessionHasErrors('unit_bobbins');
    }

    public function test_allocated_carton_cannot_be_edited(): void
    {
        $user = $this->warehouseUser();
        $this->actingAs($user)->post(route('warehouse.cartons.store'), $this->payload());
        $carton = DtyCarton::firstOrFail();
        $carton->rolls()->first()->update(['status' => RollStatus::Allocated]);

        $this->actingAs($user)->get(route('warehouse.cartons.edit', $carton))->assertForbidden();
        $this->actingAs($user)->put(route('warehouse.cartons.update', $carton), $this->payload())->assertForbidden();
    }

    public function test_carton_label_renders_identity_and_specification(): void
    {
        $user = $this->warehouseUser();
        $this->actingAs($user)->post(route('warehouse.cartons.store'), $this->payload());
        $carton = DtyCarton::firstOrFail();

        $this->actingAs($user)
            ->get(route('warehouse.cartons.label', $carton))
            ->assertOk()
            ->assertSee('ADMG00054918')
            ->assertSee('4D9164')
            ->assertSee('150/48 NI Z')
            ->assertSee('35.56')
            ->assertSee('32.91')
            ->assertSee($carton->rolls->first()->nomor_seri);
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
