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

    public function test_customer_detail_fields_are_saved_and_normalized(): void
    {
        $logisticsUser = User::factory()->role(UserRole::Logistik)->create();

        $this->actingAs($logisticsUser)
            ->post(route('logistics.customers.store'), [
                'kode_pelanggan' => 'cust-0001',
                'nama_pabrik' => 'PT Contoh Tekstil',
                'email' => 'PURCHASING@CONTOH.CO.ID',
                'npwp' => '01.234.567.8-901.000',
                'alamat' => 'Jl. Industri No. 10',
                'kota' => 'Bandung',
                'provinsi' => 'Jawa Barat',
                'kode_pos' => '40123',
                'kontak_nama' => 'Sari',
                'kontak_jabatan' => 'Purchasing',
                'kontak_telepon' => '08123456789',
                'catatan' => 'Hubungi sebelum masuk gerbang.',
            ])
            ->assertRedirect(route('logistics.customers.index'));

        $this->assertDatabaseHas('customers', [
            'kode_pelanggan' => 'CUST-0001',
            'email' => 'purchasing@contoh.co.id',
            'nama_pabrik' => 'PT Contoh Tekstil',
            'kota' => 'Bandung',
            'provinsi' => 'Jawa Barat',
            'kontak_jabatan' => 'Purchasing',
        ]);
    }

    public function test_customer_code_must_be_unique(): void
    {
        $logisticsUser = User::factory()->role(UserRole::Logistik)->create();
        Customer::factory()->create(['kode_pelanggan' => 'CUST-0001']);

        $this->actingAs($logisticsUser)
            ->post(route('logistics.customers.store'), [
                'kode_pelanggan' => 'CUST-0001',
                'nama_pabrik' => 'Pabrik Lain',
                'alamat' => 'Jl. Lain No. 1',
                'kontak_nama' => 'Budi',
                'kontak_telepon' => '081200000000',
            ])
            ->assertSessionHasErrors('kode_pelanggan');
    }

    public function test_customer_form_renders_map_picker(): void
    {
        $logisticsUser = User::factory()->role(UserRole::Logistik)->create();
        $customer = Customer::factory()->create();

        $this->actingAs($logisticsUser)
            ->get(route('logistics.customers.create'))
            ->assertOk()
            ->assertSee('customer-map', false)
            ->assertSee('Gunakan lokasi saya')
            ->assertSee('id="latitude"', false)
            ->assertSee('id="longitude"', false);

        $this->actingAs($logisticsUser)
            ->get(route('logistics.customers.edit', $customer))
            ->assertOk()
            ->assertSee('customer-map', false);
    }

    public function test_customer_manual_fields_have_placeholders(): void
    {
        $logisticsUser = User::factory()->role(UserRole::Logistik)->create();

        $this->actingAs($logisticsUser)
            ->get(route('logistics.customers.create'))
            ->assertOk()
            ->assertSee('name="kode_pelanggan"', false)
            ->assertSee('Contoh: CUST-0001')
            ->assertSee('Contoh: Bandung')
            ->assertSee('Contoh: Jawa Barat')
            ->assertSee('Contoh: 40123');
    }
}
