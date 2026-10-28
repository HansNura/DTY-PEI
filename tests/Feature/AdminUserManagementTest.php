<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_user(): void
    {
        $admin = User::factory()->role(UserRole::Admin)->create();

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'nama' => 'Budi Gudang',
            'email' => 'BUDI@PMT.TEST',
            'role' => UserRole::AdminGudang->value,
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'nama' => 'Budi Gudang',
            'email' => 'budi@pmt.test',
            'role' => UserRole::AdminGudang->value,
            'is_active' => true,
        ]);
    }

    public function test_email_must_be_unique(): void
    {
        $admin = User::factory()->role(UserRole::Admin)->create();
        User::factory()->create(['email' => 'budi@pmt.test']);

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'nama' => 'Budi Lain',
            'email' => 'budi@pmt.test',
            'role' => UserRole::Driver->value,
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertSessionHasErrors('email');
    }

    public function test_role_must_be_valid(): void
    {
        $admin = User::factory()->role(UserRole::Admin)->create();

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'nama' => 'Pengguna Baru',
            'email' => 'baru@pmt.test',
            'role' => 'superadmin',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertSessionHasErrors('role');
    }

    public function test_password_is_optional_when_updating_a_user(): void
    {
        $admin = User::factory()->role(UserRole::Admin)->create();
        $user = User::factory()->create(['password' => 'password']);

        $this->actingAs($admin)->put(route('admin.users.update', $user), [
            'nama' => 'Nama Baru',
            'email' => $user->email,
            'role' => UserRole::Logistik->value,
            'password' => '',
            'password_confirmation' => '',
            'is_active' => '1',
        ])->assertRedirect(route('admin.users.index'));

        $user->refresh();

        $this->assertSame('Nama Baru', $user->nama);
        $this->assertSame(UserRole::Logistik, $user->role);
        $this->assertTrue(Hash::check('password', $user->password));
    }

    public function test_admin_cannot_change_own_role(): void
    {
        $admin = User::factory()->role(UserRole::Admin)->create();

        $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'nama' => $admin->nama,
            'email' => $admin->email,
            'role' => UserRole::Driver->value,
            'is_active' => '1',
        ])->assertSessionHasErrors('role');

        $this->assertSame(UserRole::Admin, $admin->fresh()->role);
    }

    public function test_admin_cannot_deactivate_own_account(): void
    {
        $admin = User::factory()->role(UserRole::Admin)->create();

        $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'nama' => $admin->nama,
            'email' => $admin->email,
            'role' => UserRole::Admin->value,
            'is_active' => '0',
        ])->assertSessionHasErrors('is_active');

        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_admin_can_toggle_another_users_status(): void
    {
        $admin = User::factory()->role(UserRole::Admin)->create();
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($admin)
            ->patch(route('admin.users.toggle-active', $user))
            ->assertRedirect();

        $this->assertFalse($user->fresh()->is_active);

        $this->actingAs($admin)
            ->patch(route('admin.users.toggle-active', $user))
            ->assertRedirect();

        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_admin_cannot_toggle_own_status(): void
    {
        $admin = User::factory()->role(UserRole::Admin)->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.toggle-active', $admin))
            ->assertSessionHasErrors('user');

        $this->assertTrue($admin->fresh()->is_active);
    }
}
