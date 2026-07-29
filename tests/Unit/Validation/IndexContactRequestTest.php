<?php

namespace Tests\Unit\Validation;

use App\Http\Requests\IndexContactRequest;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class IndexContactRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * キーワードフィルタを受け付けることをテスト
     */
    public function test_keyword_filter_passes_validation(): void
    {
        // 1. 準備（Arrange）：正しいキーワードを用意する
        $input = [
            'keyword' => '山田',
        ];

        $request = new IndexContactRequest;

        // 2. 実行（Act）：問い合わせ一覧検索用のルールでバリデーションする
        $validator = Validator::make(
            $input,
            $request->rules()
        );

        // 3. 検証（Assert）：キーワードが受け付けられるか確認する
        $this->assertTrue($validator->passes());
    }

    /**
     * 性別フィルタを受け付けることをテスト
     */
    public function test_gender_filter_passes_validation(): void
    {
        // 1. 準備（Arrange）：許可されている性別の値を用意する
        $input = [
            'gender' => 1,
        ];

        $request = new IndexContactRequest;

        // 2. 実行（Act）：問い合わせ一覧検索用のルールでバリデーションする
        $validator = Validator::make(
            $input,
            $request->rules()
        );

        // 3. 検証（Assert）：性別フィルタが受け付けられるか確認する
        $this->assertTrue($validator->passes());
    }

    /**
     * カテゴリフィルタを受け付けることをテスト
     */
    public function test_category_filter_passes_validation(): void
    {
        // 1. 準備（Arrange）：存在するカテゴリを作成する
        $category = Category::create([
            'content' => '商品のお届けについて',
        ]);

        $input = [
            'category_id' => $category->id,
        ];

        $request = new IndexContactRequest;

        // 2. 実行（Act）：問い合わせ一覧検索用のルールでバリデーションする
        $validator = Validator::make(
            $input,
            $request->rules()
        );

        // 3. 検証（Assert）：カテゴリフィルタが受け付けられるか確認する
        $this->assertTrue($validator->passes());
    }

    /**
     * 日付フィルタを受け付けることをテスト
     */
    public function test_date_filter_passes_validation(): void
    {
        // 1. 準備（Arrange）：正しい形式の日付を用意する
        $input = [
            'date' => '2026-07-29',
        ];

        $request = new IndexContactRequest;

        // 2. 実行（Act）：問い合わせ一覧検索用のルールでバリデーションする
        $validator = Validator::make(
            $input,
            $request->rules()
        );

        // 3. 検証（Assert）：日付フィルタが受け付けられるか確認する
        $this->assertTrue($validator->passes());
    }

    /**
     * 不正な性別が拒否されることをテスト
     */
    public function test_invalid_gender_fails_validation(): void
    {
        // 1. 準備（Arrange）：許可されていない性別の値を用意する
        $input = [
            'gender' => 4,
        ];

        $request = new IndexContactRequest;

        // 2. 実行（Act）：問い合わせ一覧検索用のルールでバリデーションする
        $validator = Validator::make(
            $input,
            $request->rules()
        );

        // 3. 検証（Assert）：genderにバリデーションエラーがあるか確認する
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('gender'));
    }
}
