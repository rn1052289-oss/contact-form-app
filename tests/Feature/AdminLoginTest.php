<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_displayed(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertViewIs('auth.login');
        $response->assertSee('メールアドレス');
        $response->assertSee('パスワード');
        $response->assertSee('ログイン');
        $response->assertSee('href="'.route('register').'"', false);
    }

    public function test_email_is_required_for_login(): void
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);

        $this->assertGuest();
    }

    public function test_password_is_required_for_login(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);

        $this->assertGuest();
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $response = $this->post('/login', [
            'email' => 'not-found@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);

        $this->assertGuest();
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_is_rate_limited_after_five_failed_attempts(): void
    {
        $credentials = [
            'email' => 'rate-limit@example.com',
            'password' => 'password123',
        ];

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $response = $this->post('/login', $credentials);

            $response->assertSessionHasErrors('email');
        }

        $response = $this->post('/login', $credentials);

        $response->assertStatus(429);
        $this->assertGuest();
    }
}
