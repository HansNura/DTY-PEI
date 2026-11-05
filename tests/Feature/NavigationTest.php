<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    private function user(UserRole $role): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    public function test_admin_sees_user_management_and_not_operational_modules(): void
    {
        $this->actingAs($this->user(UserRole::Admin))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Pengguna')
            ->assertDontSee('Live Tracking')
            ->assertDontSee('Scanner Muatan');
    }

    public function test_warehouse_user_sees_rolls_and_scanner(): void
    {
        $this->actingAs($this->user(UserRole::AdminGudang))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Kardus DTY')
            ->assertSee('Stok Bobbin')
            ->assertSee('Scanner Muatan')
            ->assertDontSee('Live Tracking');
    }

    public function test_logistics_user_sees_orders_tracking_and_master_data(): void
    {
        $this->actingAs($this->user(UserRole::Logistik))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Surat Jalan')
            ->assertSee('Live Tracking')
            ->assertSee('Pelanggan')
            ->assertSee('Armada');
    }

    public function test_driver_sees_journey_navigation_only(): void
    {
        $this->actingAs($this->user(UserRole::Driver))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Perjalanan Saya')
            ->assertDontSee('Surat Jalan')
            ->assertDontSee('Live Tracking');
    }
}
