<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ContactAuthenticationApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider contactEndpoints
     */
    public function test_guests_cannot_access_contact_endpoints(string $method, bool $hasId): void
    {
        $contact = Contact::factory()->create();
        $original = $contact->fresh()->getAttributes();
        $url = '/api/v1/contacts'.($hasId ? '/'.$contact->id : '');

        $this->json($method, $url, ['first_name' => '変更'])->assertUnauthorized();

        $this->assertDatabaseCount('contacts', 1);
        $this->assertSame($original, $contact->fresh()->getAttributes());
    }

    public static function contactEndpoints(): array
    {
        return [
            'index' => ['GET', false],
            'store' => ['POST', false],
            'show' => ['GET', true],
            'update' => ['PUT', true],
            'destroy' => ['DELETE', true],
        ];
    }

    public function test_invalid_bearer_token_cannot_access_contacts(): void
    {
        $this->withToken('invalid-token')->getJson('/api/v1/contacts')->assertUnauthorized();
    }

    public function test_guests_receive_401_before_contact_binding(): void
    {
        $this->getJson('/api/v1/contacts/99999')->assertUnauthorized();
    }
}
