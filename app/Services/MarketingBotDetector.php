<?php

namespace App\Services;

use Illuminate\Http\Request;

class MarketingBotDetector
{
    /**
     * Czy żądanie pochodzi od bota, crawlera lub podglądu linku (np. Facebook OG scraper).
     */
    public function isBotOrPreviewCrawler(Request $request): bool
    {
        if ($this->isLikelyPreviewFetch($request)) {
            return true;
        }

        return $this->isLikelyBotUserAgent($request->userAgent());
    }

    public function isLikelyBotUserAgent(?string $userAgent): bool
    {
        $ua = strtolower(trim((string) $userAgent));

        if ($ua === '') {
            return true;
        }

        foreach ($this->excludedUserAgentSubstrings() as $substring) {
            if (str_contains($ua, strtolower($substring))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function excludedUserAgentSubstrings(): array
    {
        $configured = config('marketing.tracking_excluded_user_agent_substrings');

        return is_array($configured) ? array_values($configured) : [];
    }

    private function isLikelyPreviewFetch(Request $request): bool
    {
        $purpose = strtolower(trim((string) $request->header('Purpose', '')));
        if (in_array($purpose, ['prefetch', 'preview'], true)) {
            return true;
        }

        $secPurpose = strtolower(trim((string) $request->header('Sec-Purpose', '')));
        if ($secPurpose !== '' && (str_contains($secPurpose, 'prefetch') || str_contains($secPurpose, 'preview'))) {
            return true;
        }

        return false;
    }
}
