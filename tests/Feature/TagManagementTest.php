<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_manage_tags()
    {
        $user = User::factory()->create();

        $tag = Tag::create([
            'name' => '編集前のタグ',
        ]);

        $editResponse = $this->actingAs($user)
            ->get("/admin/tags/{$tag->id}/edit");

        $editResponse->assertStatus(200)
            ->assertViewIs('admin.tags.edit')
            ->assertViewHas('tag')
            ->assertSee('編集前のタグ');

        $storeResponse = $this->actingAs($user)
            ->post('/admin/tags', [
                'name' => '新しいタグ',
            ]);

        $storeResponse->assertRedirect('/admin');

        $this->assertDatabaseHas('tags', [
            'name' => '新しいタグ',
        ]);

        $updateResponse = $this->actingAs($user)
            ->put("/admin/tags/{$tag->id}", [
                'name' => '編集後のタグ',
            ]);

        $updateResponse->assertRedirect('/admin');

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => '編集後のタグ',
        ]);

        $deleteResponse = $this->actingAs($user)
            ->delete("/admin/tags/{$tag->id}");

        $deleteResponse->assertRedirect('/admin');

        $this->assertDatabaseMissing('tags', [
            'id' => $tag->id,
        ]);
    }

    /**
     * 未認証ユーザーがタグを操作できないことをテスト
     */
    public function test_unauthenticated_user_cannot_manage_tags()
    {
        $tag = Tag::create([
            'name' => '操作対象タグ',
        ]);

        $this->get("/admin/tags/{$tag->id}/edit")
            ->assertRedirect('/login');

        $this->post('/admin/tags', [
            'name' => '未認証作成タグ',
        ])
            ->assertRedirect('/login');

        $this->put("/admin/tags/{$tag->id}", [
            'name' => '未認証更新タグ',
        ])
            ->assertRedirect('/login');

        $this->delete("/admin/tags/{$tag->id}")
            ->assertRedirect('/login');

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => '操作対象タグ',
        ]);

        $this->assertDatabaseMissing('tags', [
            'name' => '未認証作成タグ',
        ]);
    }
}
