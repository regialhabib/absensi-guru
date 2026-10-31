<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_is_redirected_to_admin_dashboard_after_login(): void
    {
        $admin = User::factory()->admin()->create();

        $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_guru_is_redirected_to_dashboard_after_login(): void
    {
        $guru = User::factory()->guru()->create();

        $this->post(route('login.store'), ['email' => $guru->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_inactive_users_cannot_log_in(): void
    {
        $user = User::factory()->inactive()->create();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_guru_cannot_access_admin_routes(): void
    {
        $this->actingAs(User::factory()->guru()->create())
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_passes_the_admin_middleware(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_admin_cannot_use_teacher_only_routes(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('attendance.show'))->assertForbidden();
        $this->actingAs($admin)->post(route('attendance.check-in'))->assertForbidden();
        $this->actingAs($admin)->post(route('attendance.check-out'))->assertForbidden();
        $this->actingAs($admin)->get(route('attendance.history'))->assertForbidden();
        $this->actingAs($admin)->get(route('leaves.index'))->assertForbidden();
        $this->actingAs($admin)->post(route('leaves.store'))->assertForbidden();
    }

    public function test_user_deactivated_mid_session_is_logged_out(): void
    {
        $user = User::factory()->guru()->create();
        $this->actingAs($user);

        $user->update(['is_active' => false]);

        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_public_registration_is_disabled(): void
    {
        $this->assertFalse(Route::has('register'));
        $this->get('/register')->assertNotFound();
    }
}
