<?php

namespace Tests\Feature;

use Tests\TestCase;

class FunnelSkipOptOutTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'marketing.funnel_skip_token' => 'feature-test-secret',
            'marketing.funnel_skip_cookie' => 'pne_skip_funnel',
        ]);
    }

    public function test_valid_enable_link_sets_cookie_and_redirects_without_query(): void
    {
        $response = $this->get('/?pne_skip_funnel=1&token=feature-test-secret');

        $response->assertRedirect(url('/'));
        $response->assertCookie('pne_skip_funnel', '1', false);
        $response->assertSessionHas('info');
    }

    public function test_valid_disable_link_clears_cookie(): void
    {
        $response = $this->withCookie('pne_skip_funnel', '1')
            ->get('/?pne_skip_funnel=0&token=feature-test-secret');

        $response->assertRedirect(url('/'));
        $this->assertTrue(
            collect($response->headers->getCookies())->contains(
                fn ($cookie) => $cookie->getName() === 'pne_skip_funnel' && $cookie->getExpiresTime() < time()
            )
        );
    }

    public function test_wrong_token_does_not_set_cookie(): void
    {
        $response = $this->get('/?pne_skip_funnel=1&token=wrong');

        $response->assertOk();
        $response->assertCookieMissing('pne_skip_funnel');
    }
}
