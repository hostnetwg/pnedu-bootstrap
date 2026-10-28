<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * First-party operational attribution used only to connect an order with a campaign.
 *
 * This service does not enable GA/GTM, create an analytics cookie, or persist
 * attribution beyond the current Laravel session.
 */
class OrderCampaignAttributionService
{
    public const SESSION_KEY = 'marketing.order_campaign';

    public function captureFromRequest(Request $request): ?string
    {
        $campaignCode = $this->campaignCodeFromQuery($request);

        if ($campaignCode !== null && $request->hasSession()) {
            $request->session()->put(self::SESSION_KEY, [
                'campaign_code' => $campaignCode,
                'captured_at' => now()->toIso8601String(),
            ]);
        }

        return $campaignCode;
    }

    public function resolveCampaignCode(Request $request): ?string
    {
        $campaignCode = $this->campaignCodeFromQuery($request);
        if ($campaignCode !== null) {
            return $campaignCode;
        }

        if (! $request->hasSession()) {
            return null;
        }

        $state = $request->session()->get(self::SESSION_KEY, []);
        $sessionCode = is_array($state) ? ($state['campaign_code'] ?? null) : null;

        return $this->normalize($sessionCode);
    }

    private function campaignCodeFromQuery(Request $request): ?string
    {
        return $this->normalize(
            $request->query('utm_campaign', $request->query('fb', $request->query('fb_source')))
        );
    }

    private function normalize(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? Str::limit($value, 255, '') : null;
    }
}
