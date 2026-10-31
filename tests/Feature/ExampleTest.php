<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_home_to_login()
    {
        $this->get(route('home'))->assertRedirect(route('login'));
    }

    public function test_home_redirects_users_by_role()
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('home'))
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs(User::factory()->guru()->create())
            ->get(route('home'))
            ->assertRedirect(route('dashboard'));
    }
}
