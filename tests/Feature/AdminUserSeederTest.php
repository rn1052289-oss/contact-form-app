<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

final class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('admin', [
            'name' => 'テスト管理者',
            'email' => 'seeded-admin@example.com',
            'password' => 'admin-test-password',
        ]);
    }

    public function test_seeder_creates_one_admin_and_can_be_run_twice(): void
    {
        $this->seed(AdminUserSeeder::class);

        $this->assertDatabaseCount('users', 1);
        $user = User::query()->sole();
        $this->assertSame('テスト管理者', $user->name);
        $this->assertSame('seeded-admin@example.com', $user->email);
        $this->assertTrue(Hash::check('admin-test-password', $user->password));

        $this->seed(AdminUserSeeder::class);

        $this->assertDatabaseCount('users', 1);
        $this->assertSame($user->id, User::query()->sole()->id);
        $this->assertSame($user->password, $user->fresh()->password);
    }

    public function test_existing_user_is_not_overwritten(): void
    {
        $user = User::factory()->create([
            'name' => '既存の管理者',
            'email' => 'seeded-admin@example.com',
            'password' => Hash::make('existing-password'),
        ]);

        $this->seed(AdminUserSeeder::class);

        $this->assertDatabaseCount('users', 1);
        $this->assertSame('既存の管理者', $user->fresh()->name);
        $this->assertTrue(Hash::check('existing-password', $user->fresh()->password));
    }

    public function test_local_defaults_create_an_admin_who_can_log_in(): void
    {
        $this->app->instance('env', 'local');
        config()->set('admin', ['name' => null, 'email' => '', 'password' => null]);

        $this->seed(AdminUserSeeder::class);

        $this->assertDatabaseCount('users', 1);
        $user = User::query()->sole();
        $this->assertSame('管理者', $user->name);
        $this->assertSame('admin@example.com', $user->email);
        $this->assertTrue(Hash::check('password', $user->password));

        // HTTPリクエストはテスト環境に戻して実行する。
        $this->app->instance('env', 'testing');
        $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect('/admin');

        $this->assertAuthenticatedAs($user);
    }

    /**
     * @dataProvider missingPasswords
     */
    public function test_production_rejects_missing_password_without_creating_an_admin(?string $password): void
    {
        $this->app->instance('env', 'production');
        config()->set('admin.password', $password);

        try {
            $this->app->make(AdminUserSeeder::class)->run();
            $this->fail('ADMIN_PASSWORD未設定で管理者が作成されました。');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('ADMIN_PASSWORD', $exception->getMessage());
        }

        $this->assertDatabaseCount('users', 0);
    }

    public static function missingPasswords(): array
    {
        return [[null], [''], ['   ']];
    }

    public function test_production_creates_admin_with_configured_credentials(): void
    {
        $this->app->instance('env', 'production');

        $this->app->make(AdminUserSeeder::class)->run();

        $this->assertDatabaseCount('users', 1);
        $this->assertTrue(Hash::check('admin-test-password', User::query()->sole()->password));
    }

    public function test_database_seeder_also_creates_admin(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', ['email' => 'seeded-admin@example.com']);
    }
}
