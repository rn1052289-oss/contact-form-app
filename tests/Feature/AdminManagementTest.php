<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 名前をキーワードで部分一致検索できることをテスト
     */
    public function test_contacts_can_be_searched_by_name_keyword()
    {
        // 管理者ユーザーを作成
        $user = User::factory()->create();

        // カテゴリを作成
        $category = Category::create([
            'content' => '商品について',
        ]);

        // 検索結果に表示されるお問い合わせを作成
        $this->createContact([
            'category_id' => $category->id,
            'first_name' => '山田',
            'last_name' => '太郎',
            'email' => 'yamada@example.com',
        ]);

        // 検索結果に表示されないお問い合わせを作成
        $this->createContact([
            'category_id' => $category->id,
            'first_name' => '佐藤',
            'last_name' => '花子',
            'email' => 'sato@example.com',
        ]);

        // 「山田」で検索
        $response = $this
            ->actingAs($user)
            ->get('/admin?keyword=山田');

        // 山田だけが表示されることを確認
        $response
            ->assertStatus(200)
            ->assertSee('山田')
            ->assertDontSee('佐藤');
    }

    /**
     * メールアドレスを完全一致検索できることをテスト
     */
    public function test_contacts_can_be_searched_by_exact_email()
    {
        // 管理者ユーザーを作成
        $user = User::factory()->create();

        // カテゴリを作成
        $category = Category::create([
            'content' => '商品について',
        ]);

        // 完全一致するお問い合わせを作成
        $this->createContact([
            'category_id' => $category->id,
            'first_name' => '検索対象',
            'email' => 'target@example.com',
        ]);

        // メールアドレスの一部だけが一致するお問い合わせを作成
        $this->createContact([
            'category_id' => $category->id,
            'first_name' => '部分一致',
            'email' => 'prefix-target@example.com',
        ]);

        // メールアドレスで検索
        $response = $this
            ->actingAs($user)
            ->get('/admin?keyword=target%40example.com');

        // 完全一致するお問い合わせだけが表示されることを確認
        $response
            ->assertStatus(200)
            ->assertSee('検索対象')
            ->assertDontSee('部分一致');
    }

    /**
     * 性別でお問い合わせを検索できることをテスト
     */
    public function test_contacts_can_be_searched_by_gender()
    {
        // 管理者ユーザーを作成
        $user = User::factory()->create();

        // カテゴリを作成
        $category = Category::create([
            'content' => '商品について',
        ]);

        // 男性のお問い合わせを作成
        $this->createContact([
            'category_id' => $category->id,
            'first_name' => '男性ユーザー',
            'gender' => 1,
        ]);

        // 女性のお問い合わせを作成
        $this->createContact([
            'category_id' => $category->id,
            'first_name' => '女性ユーザー',
            'gender' => 2,
        ]);

        // 男性で検索
        $response = $this
            ->actingAs($user)
            ->get('/admin?gender=1');

        // 男性のお問い合わせだけが表示されることを確認
        $response
            ->assertStatus(200)
            ->assertSee('男性ユーザー')
            ->assertDontSee('女性ユーザー');
    }

    /**
     * カテゴリでお問い合わせを検索できることをテスト
     */
    public function test_contacts_can_be_searched_by_category()
    {
        // カテゴリを作成
        $productCategory = Category::create([
            'content' => '商品について',
        ]);

        $supportCategory = Category::create([
            'content' => 'サポートについて',
        ]);

        // 管理者ユーザーを作成
        $user = User::factory()->create();

        // 商品カテゴリのお問い合わせを作成
        $this->createContact([
            'category_id' => $productCategory->id,
            'first_name' => '商品ユーザー',
        ]);

        // サポートカテゴリのお問い合わせを作成
        $this->createContact([
            'category_id' => $supportCategory->id,
            'first_name' => 'サポートユーザー',
        ]);

        // 商品カテゴリで検索
        $response = $this
            ->actingAs($user)
            ->get("/admin?category_id={$productCategory->id}");

        // 商品カテゴリのお問い合わせだけが表示されることを確認
        $response
            ->assertStatus(200)
            ->assertSee('商品ユーザー')
            ->assertDontSee('サポートユーザー');
    }

    /**
     * 作成日でお問い合わせを検索できることをテスト
     */
    public function test_contacts_can_be_searched_by_date()
    {
        // 管理者ユーザーを作成
        $user = User::factory()->create();

        // カテゴリを作成
        $category = Category::create([
            'content' => '商品について',
        ]);

        // 検索対象日のお問い合わせを作成
        $this->createContact([
            'category_id' => $category->id,
            'first_name' => '対象日ユーザー',
            'created_at' => '2026-07-20 10:00:00',
        ]);

        // 別の日のお問い合わせを作成
        $this->createContact([
            'category_id' => $category->id,
            'first_name' => '別日ユーザー',
            'created_at' => '2026-07-21 10:00:00',
        ]);

        // 日付で検索
        $response = $this
            ->actingAs($user)
            ->get('/admin?date=2026-07-20');

        // 指定した日のお問い合わせだけが表示されることを確認
        $response
            ->assertStatus(200)
            ->assertSee('対象日ユーザー')
            ->assertDontSee('別日ユーザー');
    }

    /**
     * お問い合わせが7件ごとに表示されることをテスト
     */
    public function test_contacts_are_paginated_by_seven()
    {
        // 管理者ユーザーを作成
        $user = User::factory()->create();

        // カテゴリを作成
        $category = Category::create([
            'content' => '商品について',
        ]);

        // お問い合わせを8件作成
        for ($contactNumber = 1; $contactNumber <= 8; $contactNumber++) {
            $this->createContact([
                'category_id' => $category->id,
                'first_name' => "ユーザー{$contactNumber}",
            ]);
        }

        // 管理画面を表示
        $response = $this
            ->actingAs($user)
            ->get('/admin');

        // 1ページに7件表示されることを確認
        $response
            ->assertStatus(200)
            ->assertViewHas('contacts', function ($contacts) {
                return $contacts->count() === 7
                    && $contacts->perPage() === 7
                    && $contacts->total() === 8;
            });
    }

    /**
     * お問い合わせ詳細がカテゴリ情報付きで表示されることをテスト
     */
    public function test_admin_can_view_contact_details_with_category()
    {
        // 管理者ユーザーを作成
        $user = User::factory()->create();

        // カテゴリを作成
        $category = Category::create([
            'content' => '商品について',
        ]);

        // お問い合わせを作成
        $contact = $this->createContact([
            'category_id' => $category->id,
            'first_name' => '山田',
            'last_name' => '太郎',
            'email' => 'yamada@example.com',
        ]);

        // お問い合わせ詳細ページを表示
        $response = $this
            ->actingAs($user)
            ->get("/admin/contacts/{$contact->id}");

        // お問い合わせとカテゴリが表示されることを確認
        $response
            ->assertStatus(200)
            ->assertViewIs('admin.show')
            ->assertViewHas('contact')
            ->assertSee('山田')
            ->assertSee('太郎')
            ->assertSee('yamada@example.com')
            ->assertSee('商品について');
    }

    /**
     * テスト用のお問い合わせを作成
     */
    private function createContact(array $attributes = [])
    {
        $createdAt = $attributes['created_at'] ?? now();

        unset($attributes['created_at']);

        $contact = Contact::create(array_merge([
            'category_id' => 1,
            'first_name' => 'テスト',
            'last_name' => 'ユーザー',
            'gender' => 3,
            'email' => uniqid('contact-', true).'@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区1-1-1',
            'building' => null,
            'detail' => 'テストのお問い合わせ内容です。',
        ], $attributes));

        $contact->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->save();

        return $contact;
    }
}
