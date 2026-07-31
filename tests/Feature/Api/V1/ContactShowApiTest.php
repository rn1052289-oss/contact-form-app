<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactShowApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * お問い合わせ詳細をJSON形式で取得できることをテスト
     */
    public function test_contact_can_be_retrieved_as_json(): void
    {
        // Arrange
        $category = Category::factory()->create([
            'content' => '商品のお届けについて',
        ]);

        $tag = Tag::factory()->create([
            'name' => '配送',
        ]);

        $contact = Contact::factory()
            ->for($category)
            ->create([
                'first_name' => '山田',
                'last_name' => '太郎',
                'gender' => 1,
                'email' => 'yamada@example.com',
            ]);

        $contact->tags()->attach($tag);

        // Act
        $response = $this->getJson(
            "/api/v1/contacts/{$contact->id}"
        );

        // Assert
        $response
            ->assertOk()
            ->assertJsonPath('data.id', $contact->id)
            ->assertJsonPath('data.first_name', '山田')
            ->assertJsonPath('data.last_name', '太郎')
            ->assertJsonPath('data.category.id', $category->id)
            ->assertJsonPath('data.tags.0.id', $tag->id)
            ->assertJsonStructure([
                'data' => [
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
                    'tags' => ['*' => ['id', 'name']],
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    /**
     * 存在しないお問い合わせIDを指定した場合に404が返ることをテスト
     */
    public function test_nonexistent_contact_id_returns_404(): void
    {
        // Act
        $response = $this->getJson(
            '/api/v1/contacts/99999'
        );

        // Assert
        $response
            ->assertNotFound()
            ->assertJson([
                'error' => 'お問い合わせが見つかりませんでした。',
            ]);
    }
}
