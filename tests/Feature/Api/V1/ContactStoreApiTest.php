<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactStoreApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * お問い合わせを作成して201が返ることをテスト
     */
    public function test_contact_can_be_created_and_returns_201(): void
    {
        // Arrange
        $category = Category::factory()->create();

        $data = [
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'yamada@example.com',
            'tel' => '09012345678',
            'address' => '東京都千代田区1-1',
            'building' => 'テストビル101',
            'category_id' => $category->id,
            'detail' => '商品の配送状況を確認したいです。',
        ];

        // Act
        $response = $this->postJson(
            '/api/v1/contacts',
            $data
        );

        // Assert
        $response
            ->assertCreated()
            ->assertJsonPath('data.first_name', '山田')
            ->assertJsonPath('data.last_name', '太郎')
            ->assertJsonPath('data.category.id', $category->id);

        $this->assertDatabaseHas('contacts', $data);
    }

    /**
     * お問い合わせ作成時に指定したタグが保存されることをテスト
     */
    public function test_contact_can_be_created_with_tags(): void
    {
        // Arrange
        $category = Category::factory()->create();

        $tags = Tag::factory()
            ->count(2)
            ->create();

        $data = [
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'yamada@example.com',
            'tel' => '09012345678',
            'address' => '東京都千代田区1-1',
            'building' => null,
            'category_id' => $category->id,
            'detail' => '商品の配送状況を確認したいです。',
            'tag_ids' => $tags->pluck('id')->all(),
        ];

        // Act
        $response = $this->postJson(
            '/api/v1/contacts',
            $data
        );

        // Assert
        $response
            ->assertCreated()
            ->assertJsonCount(2, 'data.tags');

        $contactId = $response->json('data.id');

        foreach ($tags as $tag) {
            $this->assertDatabaseHas('contact_tag', [
                'contact_id' => $contactId,
                'tag_id' => $tag->id,
            ]);
        }
    }

    /**
     * バリデーションエラー時に422が返ることをテスト
     */
    public function test_validation_errors_return_422(): void
    {
        // Act
        $response = $this->postJson(
            '/api/v1/contacts',
            []
        );

        // Assert
        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'first_name',
                'last_name',
                'gender',
                'email',
                'tel',
                'address',
                'category_id',
                'detail',
            ]);

        $this->assertDatabaseCount('contacts', 0);
    }
}
