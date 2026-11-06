<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\AccessMatrix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminAccessReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_access_summary(): void
    {
        $admin = User::factory()->role(UserRole::Admin)->create(['nama' => 'Sari Puspita']);
        User::factory()->role(UserRole::AdminGudang)->create();
        User::factory()->role(UserRole::Logistik)->create();
        User::factory()->role(UserRole::Driver)->create();

        $this->actingAs($admin)
            ->get(route('admin.access.index'))
            ->assertOk()
            ->assertSee('Ringkasan hak akses')
            ->assertSee('Matriks akses per peran')
            ->assertSee('Manajemen pengguna')
            ->assertSee('Scanner muatan')
            ->assertSee('Surat jalan')
            ->assertSee('Perjalanan driver')
            ->assertSee('Admin Gudang')
            ->assertSee('Tim Logistik')
            ->assertSee('Driver')
            ->assertSee('Aturan penting');
    }

    #[DataProvider('nonAdminRoles')]
    public function test_non_admin_roles_cannot_view_access_summary(UserRole $role): void
    {
        $this->actingAs(User::factory()->role($role)->create())
            ->get(route('admin.access.index'))
            ->assertForbidden();
    }

    /**
     * @return array<string, array{UserRole}>
     */
    public static function nonAdminRoles(): array
    {
        return [
            'admin gudang' => [UserRole::AdminGudang],
            'logistik' => [UserRole::Logistik],
            'driver' => [UserRole::Driver],
        ];
    }

    public function test_access_matrix_matches_route_role_middleware(): void
    {
        $modules = collect(AccessMatrix::modules())->keyBy('key');

        $this->assertSame(UserRole::cases(), $modules['dashboard']['roles']);
        $this->assertSame(UserRole::cases(), $modules['profile']['roles']);
        $this->assertSame([UserRole::Admin], $modules['admin.users']['roles']);
        $this->assertSame([UserRole::Admin], $modules['admin.access']['roles']);
        $this->assertSame([UserRole::AdminGudang], $modules['warehouse.rolls']['roles']);
        $this->assertSame([UserRole::Logistik], $modules['logistics.orders']['roles']);
        $this->assertSame([UserRole::Driver], $modules['driver.deliveries']['roles']);
        $this->assertSame([UserRole::Logistik, UserRole::Driver], $modules['deliveries.signature']['roles']);
    }
}
