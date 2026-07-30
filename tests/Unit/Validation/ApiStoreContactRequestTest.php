<?php

namespace Tests\Unit\Validation;

use App\Http\Requests\Api\V1\StoreContactRequest;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ApiStoreContactRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 全必須項目とタグ入力を受け付けることをテスト
     */
    public function test_required_fields_and_tags_pass_validation(): void
    {
        // Arrange
        $category = Category::create([
            'content' => '商品のお届けについて',
        ]);

        $firstTag = Tag::create([
            'name' => '配送',
        ]);

        $secondTag = Tag::create([
            'name' => '至急',
        ]);

        $input = [
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'taro@example.com',
            'tel' => '09012345678',
            'address' => '東京都千代田区1-1',
            'building' => 'テストビル101',
            'category_id' => $category->id,
            'detail' => '商品の配送状況を確認したいです。',
            'tag_ids' => [
                $firstTag->id,
                $secondTag->id,
            ],
        ];

        $request = new StoreContactRequest;

        // Act
        $validator = Validator::make(
            $input,
            $request->rules()
        );

        // Assert
        $this->assertTrue($validator->passes());
    }

    /**
     * 必須項目の不足と不正な値を拒否することをテスト
     */
    public function test_missing_and_invalid_values_fail_validation(): void
    {
        // Arrange
        $category = Category::create([
            'content' => '商品のお届けについて',
        ]);

        $tag = Tag::create([
            'name' => '配送',
        ]);

        $validInput = [
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'taro@example.com',
            'tel' => '09012345678',
            'address' => '東京都千代田区1-1',
            'building' => 'テストビル101',
            'category_id' => $category->id,
            'detail' => '商品の配送状況を確認したいです。',
            'tag_ids' => [
                $tag->id,
            ],
        ];

        $requiredFields = [
            'first_name',
            'last_name',
            'gender',
            'email',
            'tel',
            'address',
            'category_id',
            'detail',
        ];

        $request = new StoreContactRequest;

        // Act・Assert：必須項目が不足している場合
        foreach ($requiredFields as $requiredField) {
            $input = $validInput;
            $input[$requiredField] = null;

            $validator = Validator::make(
                $input,
                $request->rules()
            );

            $this->assertTrue(
                $validator->fails(),
                "{$requiredField}が未入力でも拒否されませんでした。"
            );

            $this->assertTrue(
                $validator->errors()->has($requiredField),
                "{$requiredField}のバリデーションエラーが取得できませんでした。"
            );
        }

        $invalidCases = [
            'gender' => [
                'input' => [
                    'gender' => 4,
                ],
                'errorKey' => 'gender',
            ],
            'email' => [
                'input' => [
                    'email' => 'invalid-email',
                ],
                'errorKey' => 'email',
            ],
            'tel' => [
                'input' => [
                    'tel' => '090-1234-5678',
                ],
                'errorKey' => 'tel',
            ],
            'category_id' => [
                'input' => [
                    'category_id' => 99999,
                ],
                'errorKey' => 'category_id',
            ],
            'detail' => [
                'input' => [
                    'detail' => str_repeat('あ', 121),
                ],
                'errorKey' => 'detail',
            ],
            'tag_ids' => [
                'input' => [
                    'tag_ids' => 'invalid-tags',
                ],
                'errorKey' => 'tag_ids',
            ],
            'tag_ids.*' => [
                'input' => [
                    'tag_ids' => [99999],
                ],
                'errorKey' => 'tag_ids.0',
            ],
        ];

        // Act・Assert：不正な値が指定された場合
        foreach ($invalidCases as $caseName => $invalidCase) {
            $input = array_replace(
                $validInput,
                $invalidCase['input']
            );

            $validator = Validator::make(
                $input,
                $request->rules()
            );

            $this->assertTrue(
                $validator->fails(),
                "{$caseName}の不正な値が拒否されませんでした。"
            );

            $this->assertTrue(
                $validator->errors()->has($invalidCase['errorKey']),
                "{$caseName}のバリデーションエラーが取得できませんでした。"
            );
        }
    }
}
