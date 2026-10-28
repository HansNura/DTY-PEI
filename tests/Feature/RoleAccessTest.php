<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

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

    #[DataProvider('nonAdminRoles')]
    public function test_non_admin_roles_cannot_access_user_management(UserRole $role): void
    {
        $user = User::factory()->role($role)->create();

        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.users.create'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.users.store'), [])->assertForbidden();
    }

    public function test_admin_can_access_user_management(): void
    {
        $admin = User::factory()->role(UserRole::Admin)->create();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Manajemen pengguna');
    }

    public function test_admin_dashboard_shows_user_statistics(): void
    {
        $admin = User::factory()->role(UserRole::Admin)->create();
        User::factory()->count(3)->role(UserRole::Driver)->create();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Total pengguna')
            ->assertSee('4');
    }
}
