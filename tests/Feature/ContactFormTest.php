<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_page_is_displayed_with_categories_and_tags(): void
    {
        $category = Category::create([
            'content' => '商品のお届けについて',
        ]);

        $tag = Tag::create([
            'name' => '質問',
        ]);

        $response = $this->get('/');

        $response->assertOk();

        $response->assertViewIs('contact.index');

        $response->assertViewHas(
            'categories',
            fn ($categories) => $categories->contains('id', $category->id)
        );

        $response->assertViewHas(
            'tags',
            fn ($tags) => $tags->contains('id', $tag->id)
        );

        $response
            ->assertSee('商品のお届けについて')
            ->assertSee('質問');
    }

    public function test_thanks_page_is_displayed(): void
    {
        $response = $this->get('/thanks');

        $response->assertOk();

        $response->assertViewIs('contact.thanks');

        $response->assertSee('お問い合わせありがとうございました');
    }

    public function test_contact_confirm_page_is_displayed_when_validation_passes(): void
    {
        $category = Category::create([
            'content' => '商品の交換について',
        ]);

        $tag = Tag::create([
            'name' => '質問',
        ]);

        $data = [
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'taro@example.com',
            'tel' => '09012345678',
            'tel1' => '090',
            'tel2' => '1234',
            'tel3' => '5678',
            'address' => '東京都渋谷区千駄ヶ谷1-2-3',
            'building' => '千駄ヶ谷マンション305',
            'category_id' => $category->id,
            'tag_ids' => [$tag->id],
            'detail' => '商品の交換について確認したいです。',
        ];

        $response = $this->post(
            route('contacts.confirm'),
            $data
        );

        $response
            ->assertOk()
            ->assertViewIs('contact.confirm')
            ->assertViewHas('validated', function (array $validated) use ($data) {
                return $validated['first_name'] === $data['first_name']
                    && $validated['last_name'] === $data['last_name']
                    && $validated['gender'] === $data['gender']
                    && $validated['email'] === $data['email']
                    && $validated['tel'] === $data['tel']
                    && $validated['address'] === $data['address']
                    && $validated['building'] === $data['building']
                    && $validated['category_id'] === $data['category_id']
                    && $validated['tag_ids'] === $data['tag_ids']
                    && $validated['detail'] === $data['detail'];
            })
            ->assertViewHas('category', function (Category $viewCategory) use ($category) {
                return $viewCategory->is($category);
            })
            ->assertViewHas('tags', function ($tags) use ($tag) {
                return $tags->contains('id', $tag->id);
            });

        $response
            ->assertSeeText('山田')
            ->assertSeeText('太郎')
            ->assertSeeText('男性')
            ->assertSeeText('taro@example.com')
            ->assertSeeText('09012345678')
            ->assertSeeText('東京都渋谷区千駄ヶ谷1-2-3')
            ->assertSeeText('千駄ヶ谷マンション305')
            ->assertSeeText('商品の交換について')
            ->assertSeeText('質問')
            ->assertSeeText('商品の交換について確認したいです。');
    }

    public function test_contact_confirm_page_redirects_with_errors_when_validation_fails(): void
    {
        $inputPageUrl = route('contact.index');

        $data = [];

        $response = $this
            ->from($inputPageUrl)
            ->post(route('contacts.confirm'), $data);

        $response->assertRedirect($inputPageUrl);

        $response->assertSessionHasErrors([
            'first_name',
            'last_name',
            'gender',
            'email',
            'tel',
            'address',
            'category_id',
            'detail',
        ]);
    }

    public function test_contact_and_selected_tag_are_saved(): void
    {
        $category = Category::create([
            'content' => '商品の交換について',
        ]);

        $tag = Tag::create([
            'name' => '質問',
        ]);

        $data = [
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'taro@example.com',
            'tel' => '09012345678',
            'tel1' => '090',
            'tel2' => '1234',
            'tel3' => '5678',
            'address' => '東京都渋谷区千駄ヶ谷1-2-3',
            'building' => '千駄ヶ谷マンション305',
            'category_id' => $category->id,
            'tag_ids' => [$tag->id],
            'detail' => '商品の交換について確認したいです。',
        ];

        $response = $this->post(
            route('contacts.store'),
            $data
        );

        $response->assertRedirect(route('contact.thanks'));

        $this->assertDatabaseHas('contacts', [
            'category_id' => $category->id,
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'taro@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区千駄ヶ谷1-2-3',
            'building' => '千駄ヶ谷マンション305',
            'detail' => '商品の交換について確認したいです。',
        ]);

        $contact = Contact::where('email', 'taro@example.com')->firstOrFail();

        $this->assertDatabaseHas('contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $tag->id,
        ]);
    }

    public function test_contact_is_not_saved_when_validation_fails(): void
    {
        $data = [];

        $response = $this->post(
            route('contacts.store'),
            $data
        );

        $response
            ->assertRedirect()
            ->assertSessionHasErrors([
                'first_name',
                'last_name',
                'gender',
                'email',
                'tel',
                'address',
                'category_id',
                'detail',
            ]);

        $this->assertDatabaseCount('contacts', 0);
        $this->assertDatabaseCount('contact_tag', 0);
    }
}
