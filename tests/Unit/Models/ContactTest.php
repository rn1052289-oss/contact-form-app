<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1つのお問い合わせが特定のカテゴリに属することをテスト
     */
    public function test_contact_belongs_to_category(): void
    {
        // 1. 準備（Arrange）：カテゴリとお問い合わせを作成する
        $category = Category::create([
            'content' => '商品のお届けについて',
        ]);

        $contact = $this->createContact($category);

        // 2. 実行（Act）：お問い合わせに紐づくカテゴリを取得する
        $relatedCategory = $contact->category;

        // 3. 検証（Assert）：belongsToリレーションであることを確認する
        $this->assertInstanceOf(
            BelongsTo::class,
            $contact->category()
        );

        // 3. 検証（Assert）：作成したカテゴリが取得できることを確認する
        $this->assertSame(
            $category->id,
            $relatedCategory->id
        );
    }

    /**
     * 1つのお問い合わせに複数のタグをsyncできることをテスト
     */
    public function test_contact_can_sync_multiple_tags(): void
    {
        // 1. 準備（Arrange）：カテゴリとお問い合わせを作成する
        $category = Category::create([
            'content' => '商品のお届けについて',
        ]);

        $contact = $this->createContact($category);

        // 1. 準備（Arrange）：紐づけるタグを2件作成する
        $firstTag = Tag::create([
            'name' => '重要',
        ]);

        $secondTag = Tag::create([
            'name' => '要対応',
        ]);

        // 2. 実行（Act）：お問い合わせへ複数のタグを同期する
        $contact->tags()->sync([
            $firstTag->id,
            $secondTag->id,
        ]);

        // 2. 実行（Act）：最新状態のお問い合わせからタグIDを取得する
        $syncedTagIds = $contact
            ->fresh()
            ->tags
            ->pluck('id')
            ->all();

        // 3. 検証（Assert）：belongsToManyリレーションであることを確認する
        $this->assertInstanceOf(
            BelongsToMany::class,
            $contact->tags()
        );

        // 3. 検証（Assert）：2件のタグが紐づいていることを確認する
        $this->assertCount(2, $syncedTagIds);

        // 3. 検証（Assert）：作成した2件のタグIDが含まれていることを確認する
        $this->assertEqualsCanonicalizing(
            [
                $firstTag->id,
                $secondTag->id,
            ],
            $syncedTagIds
        );

        // 3. 検証（Assert）：中間テーブルへ紐づけが保存されていることを確認する
        $this->assertDatabaseHas('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $firstTag->id,
        ]);

        $this->assertDatabaseHas('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $secondTag->id,
        ]);
    }

    /**
     * 指定したカテゴリに属するテスト用のお問い合わせを作成する
     */
    private function createContact(Category $category): Contact
    {
        return Contact::create([
            'category_id' => $category->id,
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => 1,
            'email' => 'taro.yamada@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区1-2-3',
            'building' => 'テストビル101号室',
            'detail' => '商品の配送状況について確認したいです。',
        ]);
    }
}
