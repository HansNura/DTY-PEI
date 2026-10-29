<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_logistics_user_can_manage_a_customer_with_map_coordinates(): void
    {
        $logisticsUser = User::factory()->role(UserRole::Logistik)->create();

        $this->actingAs($logisticsUser)
            ->post(route('logistics.customers.store'), [
                'nama_pabrik' => 'Pabrik Tekstil',
                'alamat' => 'Jl. Industri No. 10',
                'kontak_nama' => 'Sari',
                'kontak_telepon' => '08123456789',
                'latitude' => '-6.2000000',
                'longitude' => '106.8166667',
            ])
            ->assertRedirect(route('logistics.customers.index'));

        $customer = Customer::firstOrFail();
        $this->assertSame('-6.2000000', $customer->latitude);
        $this->assertSame('106.8166667', $customer->longitude);

        $this->actingAs($logisticsUser)
            ->get(route('logistics.customers.index'))
            ->assertOk()
            ->assertSee('Pabrik Tekstil')
            ->assertSee('maps.google.com');

        $this->actingAs($logisticsUser)
            ->put(route('logistics.customers.update', $customer), [
                'nama_pabrik' => 'Pabrik Tekstil Baru',
                'alamat' => 'Jl. Industri No. 12',
                'kontak_nama' => 'Sari',
                'kontak_telepon' => '08123456789',
                'latitude' => '',
                'longitude' => '',
            ])
            ->assertRedirect(route('logistics.customers.index'));

        $this->assertNull($customer->fresh()->latitude);
        $this->assertNull($customer->fresh()->longitude);
    }

    public function test_customer_coordinates_must_be_valid_and_provided_together(): void
    {
        $logisticsUser = User::factory()->role(UserRole::Logistik)->create();
        $data = [
            'nama_pabrik' => 'Pabrik Tekstil',
            'alamat' => 'Jl. Industri No. 10',
            'kontak_nama' => 'Sari',
            'kontak_telepon' => '08123456789',
            'latitude' => '100',
        ];

        $this->actingAs($logisticsUser)
            ->post(route('logistics.customers.store'), $data)
            ->assertSessionHasErrors(['latitude', 'longitude']);
    }

    public function test_customer_coordinates_cannot_exceed_database_precision(): void
    {
        $logisticsUser = User::factory()->role(UserRole::Logistik)->create();

        $this->actingAs($logisticsUser)
            ->post(route('logistics.customers.store'), [
                'nama_pabrik' => 'Pabrik Tekstil',
                'alamat' => 'Jl. Industri No. 10',
                'kontak_nama' => 'Sari',
                'kontak_telepon' => '08123456789',
                'latitude' => '-6.20000001',
                'longitude' => '106.8166667',
            ])
            ->assertSessionHasErrors('latitude');
    }

    public function test_customer_can_be_deactivated_without_deleting_its_record(): void
    {
        $logisticsUser = User::factory()->role(UserRole::Logistik)->create();
        $customer = Customer::factory()->create();

        $this->actingAs($logisticsUser)
            ->patch(route('logistics.customers.toggle-active', $customer))
            ->assertRedirect();

        $this->assertFalse($customer->fresh()->is_active);
        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
    }

    public function test_only_logistics_users_can_access_customer_management(): void
    {
        $warehouseUser = User::factory()->role(UserRole::AdminGudang)->create();

        $this->actingAs($warehouseUser)->get(route('logistics.customers.index'))->assertForbidden();
    }
}
