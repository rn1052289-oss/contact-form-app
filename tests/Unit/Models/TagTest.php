<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1つのタグに複数のお問い合わせが紐づくことをテスト
     */
    public function test_tag_belongs_to_many_contacts(): void
    {
        // 1. 準備（Arrange）：お問い合わせが属するカテゴリを作成する
        $category = Category::create([
            'content' => '商品のお届けについて',
        ]);

        // 1. 準備（Arrange）：同じタグを紐づけるお問い合わせを2件作成する
        $firstContact = Contact::create([
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

        $secondContact = Contact::create([
            'category_id' => $category->id,
            'first_name' => '花子',
            'last_name' => '佐藤',
            'gender' => 2,
            'email' => 'hanako.sato@example.com',
            'tel' => '08012345678',
            'address' => '東京都新宿区4-5-6',
            'building' => null,
            'detail' => '商品の交換について確認したいです。',
        ]);

        // 1. 準備（Arrange）：お問い合わせへ紐づけるタグを作成する
        $tag = Tag::create([
            'name' => '重要',
        ]);

        // 2. 実行（Act）：1つのタグへ2件のお問い合わせを同期する
        $tag->contacts()->sync([
            $firstContact->id,
            $secondContact->id,
        ]);

        // 2. 実行（Act）：最新状態から紐づくお問い合わせIDを取得する
        $relatedContactIds = $tag
            ->fresh()
            ->contacts
            ->pluck('id')
            ->all();

        // 3. 検証（Assert）：belongsToManyリレーションであることを確認する
        $this->assertInstanceOf(
            BelongsToMany::class,
            $tag->contacts()
        );

        // 3. 検証（Assert）：2件のお問い合わせが紐づいていることを確認する
        $this->assertCount(2, $relatedContactIds);

        // 3. 検証（Assert）：作成した2件のお問い合わせIDが含まれていることを確認する
        $this->assertEqualsCanonicalizing(
            [
                $firstContact->id,
                $secondContact->id,
            ],
            $relatedContactIds
        );

        // 3. 検証（Assert）：中間テーブルに1件目の紐づけが保存されていることを確認する
        $this->assertDatabaseHas('contact_tag', [
            'contact_id' => $firstContact->id,
            'tag_id' => $tag->id,
        ]);

        // 3. 検証（Assert）：中間テーブルに2件目の紐づけが保存されていることを確認する
        $this->assertDatabaseHas('contact_tag', [
            'contact_id' => $secondContact->id,
            'tag_id' => $tag->id,
        ]);
    }
}
