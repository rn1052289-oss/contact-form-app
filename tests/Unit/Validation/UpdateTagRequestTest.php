<?php

namespace Tests\Unit\Validation;

use App\Http\Requests\UpdateTagRequest;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateTagRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 更新対象自身のタグ名を維持できることをテスト
     */
    public function test_current_tag_name_passes_validation(): void
    {
        // 1. 準備（Arrange）：更新対象となるタグを作成する
        $tag = Tag::create([
            'name' => '重要',
        ]);

        // 1. 準備（Arrange）：現在と同じタグ名を入力値として用意する
        $input = [
            'name' => '重要',
        ];

        // 1. 準備（Arrange）：更新対象のタグをルートへ設定する
        $request = $this->createUpdateTagRequest($tag);

        // 2. 実行（Act）：タグ更新用のルールでバリデーションする
        $validator = Validator::make(
            $input,
            $request->rules()
        );

        // 3. 検証（Assert）：現在と同じタグ名が受け付けられるか確認する
        $this->assertTrue($validator->passes());
    }

    /**
     * 他のタグが使用している名前への変更が拒否されることをテスト
     */
    public function test_name_used_by_another_tag_fails_validation(): void
    {
        // 1. 準備（Arrange）：更新対象となるタグを作成する
        $currentTag = Tag::create([
            'name' => '重要',
        ]);

        // 1. 準備（Arrange）：別のタグを作成する
        Tag::create([
            'name' => '要対応',
        ]);

        // 1. 準備（Arrange）：別のタグが使用している名前を用意する
        $input = [
            'name' => '要対応',
        ];

        // 1. 準備（Arrange）：更新対象のタグをルートへ設定する
        $request = $this->createUpdateTagRequest($currentTag);

        // 2. 実行（Act）：タグ更新用のルールでバリデーションする
        $validator = Validator::make(
            $input,
            $request->rules()
        );

        // 3. 検証（Assert）：nameにバリデーションエラーがあるか確認する
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
    }

    /**
     * 更新対象のタグを持つUpdateTagRequestを作成する
     */
    private function createUpdateTagRequest(Tag $tag): UpdateTagRequest
    {
        $request = UpdateTagRequest::create(
            "/admin/tags/{$tag->id}",
            'PUT'
        );

        $route = new Route(
            'PUT',
            '/admin/tags/{tag}',
            []
        );

        $route->bind($request);
        $route->setParameter('tag', $tag);

        $request->setRouteResolver(
            fn (): Route => $route
        );

        return $request;
    }
}
