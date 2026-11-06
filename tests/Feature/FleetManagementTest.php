<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Fleet;
use App\Models\User;
use Database\Seeders\FleetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FleetManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_logistics_user_can_assign_an_active_driver_to_a_fleet(): void
    {
        $logisticsUser = User::factory()->role(UserRole::Logistik)->create();
        $driver = User::factory()->role(UserRole::Driver)->create(['nama' => 'Budi Driver']);

        $this->actingAs($logisticsUser)
            ->post(route('logistics.fleets.store'), [
                'kode_armada' => 'MANUAL-999',
                'nomor_polisi' => ' b 1234 cde ',
                'jenis_kendaraan' => 'Truk Engkel',
                'kapasitas_kg' => '5000.00',
                'driver_id' => $driver->id,
            ])
            ->assertRedirect(route('logistics.fleets.index'));

        $fleet = Fleet::firstOrFail();
        $this->assertSame('ARM-0001', $fleet->kode_armada);
        $this->assertSame('B 1234 CDE', $fleet->nomor_polisi);
        $this->assertSame($driver->id, $fleet->driver_id);
        $this->assertDatabaseMissing('fleets', ['kode_armada' => 'MANUAL-999']);

        $this->actingAs($logisticsUser)
            ->get(route('logistics.fleets.index'))
            ->assertOk()
            ->assertSee('Budi Driver')
            ->assertSee('ARM-0001')
            ->assertSee('data-fleet-drawer', false)
            ->assertSee('Ringkasan');
    }

    public function test_fleet_form_has_automatic_code_and_placeholders(): void
    {
        $logisticsUser = User::factory()->role(UserRole::Logistik)->create();

        $this->actingAs($logisticsUser)
            ->get(route('logistics.fleets.create'))
            ->assertOk()
            ->assertSee('ARM-0001')
            ->assertSee('Otomatis')
            ->assertDontSee('name="kode_armada"', false)
            ->assertSee('Contoh: D 1234 AB')
            ->assertSee('Contoh: Truk Wingbox')
            ->assertSee('Contoh: 8000');
    }

    public function test_fleet_index_filters_by_search_and_status(): void
    {
        $logisticsUser = User::factory()->role(UserRole::Logistik)->create();
        Fleet::factory()->create([
            'nomor_polisi' => 'B 1001 AKT',
            'jenis_kendaraan' => 'Truk Aktif',
            'is_active' => true,
        ]);
        Fleet::factory()->create([
            'nomor_polisi' => 'B 1002 NON',
            'jenis_kendaraan' => 'Truk Nonaktif',
            'is_active' => false,
        ]);

        $this->actingAs($logisticsUser)
            ->get(route('logistics.fleets.index', ['q' => 'Truk Aktif']))
            ->assertOk()
            ->assertSee('Truk Aktif')
            ->assertDontSee('Truk Nonaktif');

        $this->actingAs($logisticsUser)
            ->get(route('logistics.fleets.index', ['status' => 'inactive']))
            ->assertOk()
            ->assertSee('Truk Nonaktif')
            ->assertDontSee('Truk Aktif');
    }

    public function test_only_active_driver_accounts_can_be_assigned(): void
    {
        $logisticsUser = User::factory()->role(UserRole::Logistik)->create();
        $inactiveDriver = User::factory()->role(UserRole::Driver)->inactive()->create();

        $this->actingAs($logisticsUser)
            ->post(route('logistics.fleets.store'), [
                'nomor_polisi' => 'B 1234 CDE',
                'jenis_kendaraan' => 'Truk Engkel',
                'kapasitas_kg' => '5000',
                'driver_id' => $inactiveDriver->id,
            ])
            ->assertSessionHasErrors('driver_id');
    }

    public function test_fleet_plate_must_be_unique_and_capacity_positive(): void
    {
        $logisticsUser = User::factory()->role(UserRole::Logistik)->create();
        Fleet::factory()->create(['nomor_polisi' => 'B 1234 CDE']);

        $this->actingAs($logisticsUser)
            ->post(route('logistics.fleets.store'), [
                'nomor_polisi' => 'b 1234 cde',
                'jenis_kendaraan' => 'Truk Engkel',
                'kapasitas_kg' => '0',
            ])
            ->assertSessionHasErrors(['nomor_polisi', 'kapasitas_kg']);
    }

    public function test_fleet_can_be_deactivated_without_deleting_its_record(): void
    {
        $logisticsUser = User::factory()->role(UserRole::Logistik)->create();
        $fleet = Fleet::factory()->create();

        $this->actingAs($logisticsUser)
            ->patch(route('logistics.fleets.toggle-active', $fleet))
            ->assertRedirect();

        $this->assertFalse($fleet->fresh()->is_active);
        $this->assertDatabaseHas('fleets', ['id' => $fleet->id]);
    }

    public function test_only_logistics_users_can_access_fleet_management(): void
    {
        $driver = User::factory()->role(UserRole::Driver)->create();

        $this->actingAs($driver)->get(route('logistics.fleets.index'))->assertForbidden();
    }

    public function test_fleet_seeder_creates_multiple_demo_fleets(): void
    {
        $this->seed(FleetSeeder::class);
        $this->seed(FleetSeeder::class);

        $this->assertDatabaseCount('fleets', 6);
        $this->assertDatabaseHas('fleets', [
            'kode_armada' => 'ARM-0002',
            'nomor_polisi' => 'B 9876 XYZ',
            'jenis_kendaraan' => 'Truk Fuso',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'driver2@pmt.test',
            'role' => 'driver',
            'nama' => 'Joko Santoso',
        ]);
    }
}
