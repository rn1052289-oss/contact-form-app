<?php

namespace Tests\Feature\Api\V1;

use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactDestroyApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * お問い合わせを物理削除して204が返ることをテスト
     */
    public function test_contact_can_delete_and_returns_204(): void
    {
        // Arrange
        $contact = Contact::factory()->create();

        // Act
        $response = $this->deleteJson(
            "/api/v1/contacts/{$contact->id}"
        );

        // Assert
        $response->assertNoContent();

        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id,
        ]);
    }

    /**
     * お問い合わせ削除時にタグとの関連も削除されることをテスト
     */
    public function test_contact_tag_relations_are_deleted_with_contact(): void
    {
        // Arrange
        $contact = Contact::factory()->create();

        $tags = Tag::factory()
            ->count(2)
            ->create();

        $contact->tags()->attach(
            $tags->pluck('id')->all()
        );

        // Act
        $response = $this->deleteJson(
            "/api/v1/contacts/{$contact->id}"
        );

        // Assert
        $response->assertNoContent();

        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id,
        ]);

        $this->assertDatabaseMissing('contact_tag', [
            'contact_id' => $contact->id,
        ]);
    }

    /**
     * 存在しないお問い合わせIDを削除した場合に404が返ることをテスト
     */
    public function test_nonexistent_contact_id_returns_404(): void
    {
        // Act
        $response = $this->deleteJson(
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
