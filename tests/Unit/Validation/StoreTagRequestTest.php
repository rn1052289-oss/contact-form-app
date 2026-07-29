<?php

namespace Tests\Unit\Validation;

use App\Http\Requests\StoreTagRequest;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreTagRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * タグ名が必須であることをテスト
     */
    public function test_tag_name_is_required(): void
    {
        // 1. 準備（Arrange）：タグ名を空にした入力値を用意する
        $input = [
            'name' => '',
        ];

        $request = new StoreTagRequest;

        // 2. 実行（Act）：タグ新規登録用のルールでバリデーションする
        $validator = Validator::make(
            $input,
            $request->rules()
        );

        // 3. 検証（Assert）：nameにバリデーションエラーがあるか確認する
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
    }

    /**
     * タグ名が50文字以内であることをテスト
     */
    public function test_tag_name_must_not_exceed_50_characters(): void
    {
        // 1. 準備（Arrange）：上限を1文字超える51文字のタグ名を用意する
        $input = [
            'name' => str_repeat('a', 51),
        ];

        $request = new StoreTagRequest;

        // 2. 実行（Act）：タグ新規登録用のルールでバリデーションする
        $validator = Validator::make(
            $input,
            $request->rules()
        );

        // 3. 検証（Assert）：nameにバリデーションエラーがあるか確認する
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
    }

    /**
     * 既に使用されているタグ名が拒否されることをテスト
     */
    public function test_duplicate_tag_name_fails_validation(): void
    {
        // 1. 準備（Arrange）：既存のタグをデータベースに登録する
        Tag::create([
            'name' => '重要',
        ]);

        // 1. 準備（Arrange）：既存タグと同じ名前を用意する
        $input = [
            'name' => '重要',
        ];

        $request = new StoreTagRequest;

        // 2. 実行（Act）：タグ新規登録用のルールでバリデーションする
        $validator = Validator::make(
            $input,
            $request->rules()
        );

        // 3. 検証（Assert）：nameにバリデーションエラーがあるか確認する
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
    }
}
