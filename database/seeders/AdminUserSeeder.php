<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = config('admin.password');
        $name = config('admin.name');
        $email = config('admin.email');

        if (app()->environment('local')) {
            $password = $password ?: 'password';
            $name = $name ?: '管理者';
            $email = $email ?: 'admin@example.com';
        }

        if (! is_string($password) || trim($password) === '') {
            throw new RuntimeException('管理者の作成にはADMIN_PASSWORDの設定が必要です。');
        }

        if (! is_string($name) || trim($name) === '' || ! is_string($email) || trim($email) === '') {
            throw new RuntimeException('管理者の作成にはADMIN_NAMEとADMIN_EMAILの設定が必要です。');
        }

        // 再実行しても、既存ユーザーの名前やパスワードを上書きしない。
        User::query()->firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make($password)],
        );
    }
}
