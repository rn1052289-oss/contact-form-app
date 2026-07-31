<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactIndexApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * お問い合わせ一覧をJSON形式で取得できることをテスト
     */
    public function test_contacts_can_be_retrieved_as_json(): void
    {
        // Arrange
        $category = Category::factory()->create();

        Contact::factory()
            ->count(3)
            ->for($category)
            ->create();

        // Act
        $response = $this->getJson('/api/v1/contacts');

        // Assert
        $response
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'category' => ['id', 'content'],
                        'first_name',
                        'last_name',
                        'gender',
                        'email',
                        'tel',
                        'address',
                        'building',
                        'detail',
                        'tags',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);
    }

    /**
     * キーワードでお問い合わせを検索できることをテスト
     */
    public function test_contacts_can_be_searched_by_keyword(): void
    {
        // Arrange
        $category = Category::factory()->create();

        Contact::factory()
            ->for($category)
            ->create([
                'first_name' => '山田',
                'last_name' => '太郎',
                'email' => 'yamada@example.com',
            ]);

        Contact::factory()
            ->for($category)
            ->create([
                'first_name' => '佐藤',
                'last_name' => '花子',
                'email' => 'sato@example.com',
            ]);

        // Act
        $response = $this->getJson('/api/v1/contacts?keyword=山田');

        // Assert
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.first_name', '山田')
            ->assertJsonPath('data.0.last_name', '太郎');
    }

    /**
     * 性別でお問い合わせを検索できることをテスト
     */
    public function test_contacts_can_be_searched_by_gender(): void
    {
        // Arrange
        $category = Category::factory()->create();

        Contact::factory()
            ->for($category)
            ->create([
                'first_name' => '山田',
                'gender' => 1,
            ]);

        Contact::factory()
            ->for($category)
            ->create([
                'first_name' => '佐藤',
                'gender' => 2,
            ]);

        // Act
        $response = $this->getJson('/api/v1/contacts?gender=1');

        // Assert
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.first_name', '山田')
            ->assertJsonPath('data.0.gender', 1);
    }

    /**
     * カテゴリーでお問い合わせを検索できることをテスト
     */
    public function test_contacts_can_be_searched_by_category(): void
    {
        // Arrange
        $productCategory = Category::factory()->create([
            'content' => '商品について',
        ]);

        $deliveryCategory = Category::factory()->create([
            'content' => '配送について',
        ]);

        Contact::factory()
            ->for($productCategory)
            ->create([
                'first_name' => '山田',
            ]);

        Contact::factory()
            ->for($deliveryCategory)
            ->create([
                'first_name' => '佐藤',
            ]);

        // Act
        $response = $this->getJson(
            "/api/v1/contacts?category_id={$productCategory->id}"
        );

        // Assert
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.first_name', '山田')
            ->assertJsonPath('data.0.category.id', $productCategory->id);
    }

    /**
     * 作成日でお問い合わせを検索できることをテスト
     */
    public function test_contacts_can_be_searched_by_date(): void
    {
        // Arrange
        $category = Category::factory()->create();

        Contact::factory()
            ->for($category)
            ->create([
                'first_name' => '山田',
                'created_at' => '2026-07-20 10:00:00',
            ]);

        Contact::factory()
            ->for($category)
            ->create([
                'first_name' => '佐藤',
                'created_at' => '2026-07-21 10:00:00',
            ]);

        // Act
        $response = $this->getJson(
            '/api/v1/contacts?date=2026-07-20'
        );

        // Assert
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.first_name', '山田');
    }

    /**
     * お問い合わせ一覧がデフォルト20件でページネーションされることをテスト
     */
    public function test_contacts_are_paginated_by_default_per_page(): void
    {
        // Arrange
        $category = Category::factory()->create();

        Contact::factory()
            ->count(21)
            ->for($category)
            ->create();

        // Act
        $response = $this->getJson(
            '/api/v1/contacts?page=2'
        );

        // Assert
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'meta' => [
                    'current_page' => 2,
                    'last_page' => 2,
                    'per_page' => 20,
                    'total' => 21,
                ],
            ]);
    }

    /**
     * 1ページあたりの件数が最大値を超えた場合に422が返ることをテスト
     */
    public function test_per_page_over_maximum_returns_422(): void
    {
        // Act
        $response = $this->getJson(
            '/api/v1/contacts?per_page=101'
        );

        // Assert
        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);
    }
}
