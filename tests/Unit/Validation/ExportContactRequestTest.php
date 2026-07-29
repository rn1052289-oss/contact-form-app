<?php

namespace Tests\Unit\Validation;

use App\Http\Requests\ExportContactRequest;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ExportContactRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 正しいCSVエクスポートのフィルタ条件を受け付けることをテスト
     */
    public function test_valid_export_filter_conditions_pass_validation(): void
    {
        // 1. 準備（Arrange）：存在するカテゴリと正しいフィルタ条件を用意する
        $category = Category::create([
            'content' => '商品のお届けについて',
        ]);

        $input = [
            'keyword' => '山田',
            'gender' => 1,
            'category_id' => $category->id,
            'date' => '2026-07-29',
        ];

        $request = new ExportContactRequest;

        // 2. 実行（Act）：CSVエクスポート用のルールでバリデーションする
        $validator = Validator::make(
            $input,
            $request->rules()
        );

        // 3. 検証（Assert）：正しいフィルタ条件が受け付けられるか確認する
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

        $request = new ExportContactRequest;

        // 2. 実行（Act）：CSVエクスポート用のルールでバリデーションする
        $validator = Validator::make(
            $input,
            $request->rules()
        );

        // 3. 検証（Assert）：genderにバリデーションエラーがあるか確認する
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('gender'));
    }

    /**
     * 存在しないカテゴリIDが拒否されることをテスト
     */
    public function test_nonexistent_category_id_fails_validation(): void
    {
        // 1. 準備（Arrange）：カテゴリを1件作成し、存在しない次のIDを用意する
        $category = Category::create([
            'content' => '商品のお届けについて',
        ]);

        $input = [
            'category_id' => $category->id + 1,
        ];

        $request = new ExportContactRequest;

        // 2. 実行（Act）：CSVエクスポート用のルールでバリデーションする
        $validator = Validator::make(
            $input,
            $request->rules()
        );

        // 3. 検証（Assert）：category_idにバリデーションエラーがあるか確認する
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('category_id'));
    }
}
