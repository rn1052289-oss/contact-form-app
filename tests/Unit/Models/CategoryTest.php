<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1つのカテゴリから紐づく複数のお問い合わせを取得できることをテスト
     */
    public function test_category_has_many_contacts(): void
    {
        // 1. 準備（Arrange）：カテゴリを1件作成する
        $category = Category::create([
            'content' => '商品のお届けについて',
        ]);

        // 1. 準備（Arrange）：同じカテゴリに紐づくお問い合わせを2件作成する
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

        // 2. 実行（Act）：カテゴリに紐づくお問い合わせを取得する
        $contacts = $category->contacts;

        // 3. 検証（Assert）：hasManyリレーションであることを確認する
        $this->assertInstanceOf(
            HasMany::class,
            $category->contacts()
        );

        // 3. 検証（Assert）：紐づくお問い合わせが2件取得できることを確認する
        $this->assertCount(2, $contacts);

        // 3. 検証（Assert）：作成した2件のお問い合わせが含まれていることを確認する
        $this->assertTrue($contacts->contains($firstContact));
        $this->assertTrue($contacts->contains($secondContact));
    }
}
