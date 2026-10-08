<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

final class TokenApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_credentials_issue_a_usable_token_stored_as_a_hash(): void
    {
        $user = User::factory()->create(['password' => Hash::make('test-password')]);

        $response = $this->postJson('/api/v1/tokens', [
            'email' => $user->email,
            'password' => 'test-password',
        ])->assertCreated()->assertJsonPath('token_type', 'Bearer');

        $token = $response->json('token');
        $storedToken = PersonalAccessToken::findToken($token);
        $this->assertNotNull($storedToken);
        $this->assertSame($user->id, $storedToken->tokenable_id);
        $this->assertSame(hash('sha256', explode('|', $token, 2)[1]), $storedToken->token);
        $response->assertJsonMissingPath('password');

        $this->withToken($token)->getJson('/api/v1/contacts')->assertOk();
    }

    public function test_invalid_credentials_return_the_same_401_and_do_not_issue_tokens(): void
    {
        $user = User::factory()->create(['password' => Hash::make('test-password')]);

        $wrongPassword = $this->postJson('/api/v1/tokens', [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ])->assertUnauthorized();

        $unknownUser = $this->postJson('/api/v1/tokens', [
            'email' => 'unknown@example.com',
            'password' => 'test-password',
        ])->assertUnauthorized();

        $this->assertSame($wrongPassword->json(), $unknownUser->json());
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_missing_credentials_are_rejected(): void
    {
        $this->postJson('/api/v1/tokens', [])
            ->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_invalid_credential_types_are_rejected(): void
    {
        $this->postJson('/api/v1/tokens', ['email' => 'invalid', 'password' => ['secret']])
            ->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_token_login_is_throttled_even_when_email_changes(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson('/api/v1/tokens', [
                'email' => "unknown{$attempt}@example.com",
                'password' => 'wrong-password',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/v1/tokens', [
            'email' => 'another@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(429)->assertHeader('Retry-After');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_revocation_deletes_only_current_token_and_rejects_its_reuse(): void
    {
        $user = User::factory()->create();
        $current = $user->createToken('current');
        $other = $user->createToken('other');
        $anotherUserToken = User::factory()->create()->createToken('another-user');

        $this->withToken($current->plainTextToken)->deleteJson('/api/v1/tokens/current')->assertNoContent();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $current->accessToken->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $other->accessToken->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $anotherUserToken->accessToken->id]);

        // 同じテスト内の次のHTTPリクエストで認証結果を持ち越さない。
        $this->app['auth']->forgetGuards();
        $this->withToken($current->plainTextToken)->getJson('/api/v1/contacts')->assertUnauthorized();

        $this->app['auth']->forgetGuards();
        $this->withToken($current->plainTextToken)->deleteJson('/api/v1/tokens/current')->assertUnauthorized();

        $this->app['auth']->forgetGuards();
        $this->withToken($other->plainTextToken)->getJson('/api/v1/contacts')->assertOk();
    }

    public function test_guests_cannot_revoke_tokens(): void
    {
        $this->deleteJson('/api/v1/tokens/current')->assertUnauthorized();
    }

    public function test_invalid_tokens_cannot_revoke_tokens(): void
    {
        $this->withToken('invalid-token')->deleteJson('/api/v1/tokens/current')->assertUnauthorized();
    }

    public function test_session_authentication_does_not_delete_other_tokens(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('existing');

        $this->actingAs($user)->deleteJson('/api/v1/tokens/current')->assertUnauthorized();

        $this->assertDatabaseHas('personal_access_tokens', ['id' => $token->accessToken->id]);
    }
}
