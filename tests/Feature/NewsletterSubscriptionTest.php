<?php

namespace Tests\Feature;

use App\Services\SendyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NewsletterSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_newsletter_subscribes_to_nauczyciele_list_in_sendy(): void
    {
        config([
            'services.sendy.url' => 'https://sendy.test',
            'services.sendy.api_key' => 'test-api-key',
        ]);

        Http::fake([
            'https://sendy.test/subscribe' => Http::response('true', 200),
        ]);

        $response = $this->post(route('newsletter.subscribe'), [
            'email' => 'nauczyciel@gmail.com',
            'newsletter_consent' => '1',
        ]);

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('newsletter_subscribed', true);
        $response->assertSessionHas('newsletter_subscribed_email', 'nauczyciel@gmail.com');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://sendy.test/subscribe'
                && $request['email'] === 'nauczyciel@gmail.com'
                && $request['list'] === SendyService::LIST_NAUCZYCIELE
                && $request['api_key'] === 'test-api-key'
                && $request['gdpr'] === 'true'
                && $request['silent'] === 'true';
        });
    }

    public function test_newsletter_requires_consent(): void
    {
        $response = $this->from(route('home'))
            ->post(route('newsletter.subscribe'), [
                'email' => 'nauczyciel@example.com',
            ]);

        $response->assertSessionHasErrors('newsletter_consent');
    }
}
