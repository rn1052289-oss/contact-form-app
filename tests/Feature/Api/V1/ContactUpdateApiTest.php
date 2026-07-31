<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactUpdateApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * お問い合わせを更新して200が返ることをテスト
     */
    public function test_contact_can_be_updated_and_returns_200(): void
    {
        // Arrange
        $originalCategory = Category::factory()->create();
        $updatedCategory = Category::factory()->create();

        $contact = Contact::factory()
            ->for($originalCategory)
            ->create([
                'first_name' => '山田',
                'last_name' => '太郎',
                'email' => 'old@example.com',
            ]);

        $data = [
            'first_name' => '佐藤',
            'last_name' => '花子',
            'gender' => 2,
            'email' => 'updated@example.com',
            'tel' => '08012345678',
            'address' => '東京都新宿区2-2',
            'building' => '更新ビル202',
            'category_id' => $updatedCategory->id,
            'detail' => '更新後のお問い合わせ内容です。',
        ];

        // Act
        $response = $this->putJson(
            "/api/v1/contacts/{$contact->id}",
            $data
        );

        // Assert
        $response
            ->assertOk()
            ->assertJsonPath('data.id', $contact->id)
            ->assertJsonPath('data.first_name', '佐藤')
            ->assertJsonPath('data.last_name', '花子')
            ->assertJsonPath('data.email', 'updated@example.com')
            ->assertJsonPath(
                'data.category.id',
                $updatedCategory->id
            );

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'first_name' => '佐藤',
            'last_name' => '花子',
            'gender' => 2,
            'email' => 'updated@example.com',
            'category_id' => $updatedCategory->id,
            'detail' => '更新後のお問い合わせ内容です。',
        ]);
    }

    /**
     * お問い合わせ更新時にタグの関連が同期されることをテスト
     */
    public function test_contact_tags_are_synced_when_updated(): void
    {
        // Arrange
        $category = Category::factory()->create();

        $oldTag = Tag::factory()->create([
            'name' => '変更前タグ',
        ]);

        $newTags = Tag::factory()
            ->count(2)
            ->create();

        $contact = Contact::factory()
            ->for($category)
            ->create();

        $contact->tags()->attach($oldTag->id);

        $data = [
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'yamada@example.com',
            'tel' => '09012345678',
            'address' => '東京都千代田区1-1',
            'building' => null,
            'category_id' => $category->id,
            'detail' => 'タグを変更したお問い合わせです。',
            'tag_ids' => $newTags->pluck('id')->all(),
        ];

        // Act
        $response = $this->putJson(
            "/api/v1/contacts/{$contact->id}",
            $data
        );

        // Assert
        $response
            ->assertOk()
            ->assertJsonCount(2, 'data.tags');

        $this->assertDatabaseMissing('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $oldTag->id,
        ]);

        foreach ($newTags as $newTag) {
            $this->assertDatabaseHas('contact_tag', [
                'contact_id' => $contact->id,
                'tag_id' => $newTag->id,
            ]);
        }
    }

    /**
     * 存在しないお問い合わせIDを更新した場合に404が返ることをテスト
     */
    public function test_nonexistent_contact_id_returns_404(): void
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
            'building' => null,
            'category_id' => $category->id,
            'detail' => '更新後のお問い合わせ内容です。',
        ];

        // Act
        $response = $this->putJson(
            '/api/v1/contacts/99999',
            $data
        );

        // Assert
        $response
            ->assertNotFound()
            ->assertJson([
                'error' => 'お問い合わせが見つかりませんでした。',
            ]);
    }

    /**
     * バリデーションエラー時に422が返り、お問い合わせが更新されないことをテスト
     */
    public function test_validation_errors_return_422_and_contact_is_not_updated(): void
    {
        // Arrange
        $category = Category::factory()->create();

        $contact = Contact::factory()
            ->for($category)
            ->create([
                'first_name' => '山田',
                'last_name' => '太郎',
                'email' => 'before@example.com',
            ]);

        // Act
        $response = $this->putJson(
            "/api/v1/contacts/{$contact->id}",
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

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'first_name' => '山田',
            'last_name' => '太郎',
            'email' => 'before@example.com',
        ]);
    }
}
