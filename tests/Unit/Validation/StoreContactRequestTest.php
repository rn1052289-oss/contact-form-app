<?php

namespace Tests\Unit\Validation;

use App\Http\Requests\StoreContactRequest;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreContactRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 全ての必須項目とタグ入力を受け付けることをテスト
     */
    public function test_required_contact_fields_and_tags_pass_validation(): void
    {
        // 1. 準備（Arrange）：存在するカテゴリとタグを作成する
        $category = Category::create([
            'content' => '商品のお届けについて',
        ]);

        $firstTag = Tag::create([
            'name' => '重要',
        ]);

        $secondTag = Tag::create([
            'name' => '要対応',
        ]);

        // 1. 準備（Arrange）：全ての必須項目とタグIDを用意する
        $input = [
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => 1,
            'email' => 'taro.yamada@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区1-2-3',
            'building' => 'テストビル101号室',
            'category_id' => $category->id,
            'detail' => '商品の配送状況について確認したいです。',
            'tag_ids' => [
                $firstTag->id,
                $secondTag->id,
            ],
        ];

        $request = new StoreContactRequest;

        // 2. 実行（Act）：問い合わせ保存用のルールでバリデーションする
        $validator = Validator::make(
            $input,
            $request->rules()
        );

        // 3. 検証（Assert）：必須項目とタグ入力が受け付けられるか確認する
        $this->assertTrue($validator->passes());
    }

    /**
     * 不正な電話番号形式が拒否されることをテスト
     */
    public function test_invalid_telephone_format_fails_validation(): void
    {
        // 1. 準備（Arrange）：存在するカテゴリを作成する
        $category = Category::create([
            'content' => '商品のお届けについて',
        ]);

        // 1. 準備（Arrange）：電話番号以外は正しい入力値を用意する
        $input = [
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => 1,
            'email' => 'taro.yamada@example.com',
            'tel' => '090-1234-5678',
            'address' => '東京都渋谷区1-2-3',
            'category_id' => $category->id,
            'detail' => '商品の配送状況について確認したいです。',
        ];

        $request = new StoreContactRequest;

        // 2. 実行（Act）：問い合わせ保存用のルールでバリデーションする
        $validator = Validator::make(
            $input,
            $request->rules()
        );

        // 3. 検証（Assert）：telにバリデーションエラーがあるか確認する
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('tel'));
    }
}
