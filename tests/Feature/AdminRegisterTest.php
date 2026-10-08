<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_page_is_not_available(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_registration_cannot_create_a_user(): void
    {
        $this->post('/register', [
            'name' => 'Unauthorized Admin',
            'email' => 'unauthorized@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertNotFound();

        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_unauthenticated_user_is_redirected_to_login_from_admin(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_admin_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/admin');

        $response->assertOk();
        $response->assertViewIs('admin.index');
    }
}
