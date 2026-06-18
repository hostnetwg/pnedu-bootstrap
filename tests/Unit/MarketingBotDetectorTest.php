<?php

namespace Tests\Unit;

use App\Services\MarketingBotDetector;
use Illuminate\Http\Request;
use Tests\TestCase;

class MarketingBotDetectorTest extends TestCase
{
    private MarketingBotDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->detector = app(MarketingBotDetector::class);
    }

    public function test_empty_user_agent_is_bot(): void
    {
        $this->assertTrue($this->detector->isLikelyBotUserAgent(''));
        $this->assertTrue($this->detector->isLikelyBotUserAgent(null));
    }

    public function test_regular_browser_is_not_bot(): void
    {
        $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';

        $this->assertFalse($this->detector->isLikelyBotUserAgent($ua));
    }

    public function test_facebook_external_hit_is_bot(): void
    {
        $ua = 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)';

        $this->assertTrue($this->detector->isLikelyBotUserAgent($ua));
    }

    public function test_meta_external_agent_is_bot(): void
    {
        $this->assertTrue($this->detector->isLikelyBotUserAgent('Meta-ExternalAgent/1.1 (+https://developers.facebook.com/docs/sharing/webmasters/crawler)'));
    }

    public function test_facebot_is_bot(): void
    {
        $this->assertTrue($this->detector->isLikelyBotUserAgent('Facebot/1.0'));
    }

    public function test_googlebot_is_bot(): void
    {
        $this->assertTrue($this->detector->isLikelyBotUserAgent('Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'));
    }

    public function test_preview_purpose_header_is_crawler_request(): void
    {
        $request = Request::create('/courses/1', 'GET', server: [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/124.0.0.0',
            'HTTP_PURPOSE' => 'preview',
        ]);

        $this->assertTrue($this->detector->isBotOrPreviewCrawler($request));
    }
}
