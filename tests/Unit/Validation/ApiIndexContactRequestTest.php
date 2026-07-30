<?php

namespace Tests\Unit\Validation;

use App\Http\Requests\Api\V1\IndexContactRequest;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ApiIndexContactRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 正しいAPI検索条件を受け付けることをテスト
     */
    public function test_valid_api_search_filters_pass_validation(): void
    {
        // Arrange
        $category = Category::create([
            'content' => '商品のお届けについて',
        ]);

        $validCases = [
            'keyword' => ['keyword' => '山田'],
            'gender_1' => ['gender' => 1],
            'gender_2' => ['gender' => 2],
            'gender_3' => ['gender' => 3],
            'category_id' => ['category_id' => $category->id],
            'date' => ['date' => '2026-07-30'],
            'per_page' => ['per_page' => 20],
        ];

        $request = new IndexContactRequest;

        // Act・Assert
        foreach ($validCases as $caseName => $input) {
            $validator = Validator::make(
                $input,
                $request->rules()
            );

            $this->assertTrue(
                $validator->passes(),
                "{$caseName}がバリデーションで拒否されました。"
            );
        }
    }

    /**
     * 不正なAPI検索条件を拒否することをテスト
     */
    public function test_invalid_api_search_filters_fail_validation(): void
    {
        // Arrange
        $category = Category::create([
            'content' => '商品のお届けについて',
        ]);

        $invalidCases = [
            'keyword' => [
                'input' => [
                    'keyword' => ['山田'],
                ],
                'errorKey' => 'keyword',
            ],
            'gender' => [
                'input' => [
                    'gender' => 4,
                ],
                'errorKey' => 'gender',
            ],
            'category_id' => [
                'input' => [
                    'category_id' => $category->id + 1,
                ],
                'errorKey' => 'category_id',
            ],
            'date' => [
                'input' => [
                    'date' => 'invalid-date',
                ],
                'errorKey' => 'date',
            ],
            'per_page' => [
                'input' => [
                    'per_page' => 0,
                ],
                'errorKey' => 'per_page',
            ],
        ];

        $request = new IndexContactRequest;

        // Act・Assert
        foreach ($invalidCases as $caseName => $invalidCase) {
            $validator = Validator::make(
                $invalidCase['input'],
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
