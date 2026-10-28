<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InactiveSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_deactivated_during_session_is_logged_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $user->update(['is_active' => false]);

        $this->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
