<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MarketingAttributionService
{
    public const SESSION_KEY = 'marketing.attribution';

    public function resolveCampaignCode(Request $request): ?string
    {
        $raw = $request->query('utm_campaign', $request->query('fb', $request->query('fb_source')));

        if (is_string($raw)) {
            $raw = trim($raw);
        }

        if ($raw !== null && $raw !== '') {
            return Str::limit($raw, 255, '');
        }

        $fromCookie = $this->readCookiePayload($request)['campaign_code'] ?? null;
        if (filled($fromCookie)) {
            return Str::limit((string) $fromCookie, 255, '');
        }

        if ($request->hasSession()) {
            $session = $request->session()->get(self::SESSION_KEY, []);
            $sessionCode = is_array($session) ? ($session['campaign_code'] ?? null) : null;
            if (filled($sessionCode)) {
                return Str::limit((string) $sessionCode, 255, '');
            }

            $legacy = $request->session()->get('marketing.fb_source');
            if (is_string($legacy) && trim($legacy) !== '') {
                return Str::limit(trim($legacy), 255, '');
            }
        }

        return null;
    }

    /**
     * @return array{campaign_code: ?string, utm_source: ?string, utm_medium: ?string, utm_content: ?string}
     */
    public function captureFromRequest(Request $request): array
    {
        $campaignCode = null;
        $rawCampaign = $request->query('utm_campaign', $request->query('fb', $request->query('fb_source')));
        if (is_string($rawCampaign) && trim($rawCampaign) !== '') {
            $campaignCode = Str::limit(trim($rawCampaign), 255, '');
        }

        $utmSource = $request->query('utm_source');
        $utmMedium = $request->query('utm_medium');
        $utmContent = $request->query('utm_content');

        $utmSource = is_string($utmSource) && trim($utmSource) !== '' ? Str::limit(trim($utmSource), 100, '') : null;
        $utmMedium = is_string($utmMedium) && trim($utmMedium) !== '' ? Str::limit(trim($utmMedium), 50, '') : null;
        $utmContent = is_string($utmContent) && trim($utmContent) !== '' ? Str::limit(trim($utmContent), 100, '') : null;

        if ($campaignCode === null && $utmSource === null && $utmMedium === null && $utmContent === null) {
            return [];
        }

        $payload = array_filter([
            'campaign_code' => $campaignCode,
            'utm_source' => $utmSource,
            'utm_medium' => $utmMedium,
            'utm_content' => $utmContent,
            'captured_at' => now()->toIso8601String(),
        ], fn ($v) => $v !== null && $v !== '');

        return $this->enrichPayloadFromCampaign($payload);
    }

    /**
     * @param  array<string, string>  $payload
     * @return array<string, string>
     */
    public function enrichPayloadFromCampaign(array $payload): array
    {
        if (empty($payload['campaign_code'])) {
            return $payload;
        }

        if (! empty($payload['utm_source']) && ! empty($payload['utm_medium'])) {
            return $payload;
        }

        $row = DB::connection('pneadm')
            ->table('marketing_campaigns as mc')
            ->leftJoin('marketing_source_types as mst', 'mst.id', '=', 'mc.source_type_id')
            ->where('mc.campaign_code', $payload['campaign_code'])
            ->whereNull('mc.deleted_at')
            ->select(['mc.utm_medium', 'mc.utm_content', 'mst.utm_source', 'mst.default_utm_medium', 'mst.default_utm_content', 'mst.slug'])
            ->first();

        if (! $row) {
            return $payload;
        }

        if (empty($payload['utm_source'])) {
            $payload['utm_source'] = $row->utm_source
                ?: match ($row->slug) {
                    'email' => 'newsletter',
                    'website' => 'pnedu',
                    'training' => 'webinar',
                    default => (string) ($row->slug ?: 'other'),
                };
        }

        if (empty($payload['utm_medium'])) {
            $payload['utm_medium'] = $row->utm_medium ?: ($row->default_utm_medium ?: 'paid');
        }

        if (empty($payload['utm_content'])) {
            $resolved = $this->resolveUtmContentFromRow($row);
            if ($resolved !== null) {
                $payload['utm_content'] = $resolved;
            }
        }

        return $payload;
    }

    /**
     * @param  object{utm_content: ?string, default_utm_content: ?string}  $row
     */
    private function resolveUtmContentFromRow(object $row): ?string
    {
        if (filled($row->utm_content ?? null)) {
            return Str::limit((string) $row->utm_content, 100, '');
        }

        $default = trim((string) ($row->default_utm_content ?? ''));

        return $default !== '' ? Str::limit($default, 100, '') : null;
    }

    public function persist(Request $request, array $payload): void
    {
        if ($payload === []) {
            return;
        }

        $payload = $this->enrichPayloadFromCampaign($payload);

        if ($request->hasSession()) {
            $request->session()->put(self::SESSION_KEY, $payload);
            if (! empty($payload['campaign_code'])) {
                $request->session()->put('marketing.fb_source', $payload['campaign_code']);
            }
        }
    }

    /**
     * @return array{campaign_code?: string, utm_source?: string, utm_medium?: string, utm_content?: string}
     */
    public function readCookiePayload(Request $request): array
    {
        $raw = $request->cookie((string) config('marketing.cookie_name', 'pne_marketing'));
        if (! is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function writeCookiePayload(array $payload): \Symfony\Component\HttpFoundation\Cookie
    {
        $minutes = max(1, (int) config('marketing.attribution_days', 7)) * 24 * 60;

        return cookie(
            (string) config('marketing.cookie_name', 'pne_marketing'),
            json_encode($payload, JSON_UNESCAPED_UNICODE),
            $minutes,
            '/',
            null,
            app()->environment('production'),
            true,
            false,
            'Lax'
        );
    }

    /**
     * Buduje query string do propagacji parametrów marketingowych w linkach wewnętrznych.
     */
    public function querySuffixForLinks(Request $request): string
    {
        $campaignCode = $this->resolveCampaignCode($request);
        if (! $campaignCode) {
            return '';
        }

        $session = $request->hasSession()
            ? $request->session()->get(self::SESSION_KEY, [])
            : [];
        $cookie = $this->readCookiePayload($request);

        $utmSource = is_array($session) ? ($session['utm_source'] ?? null) : null;
        $utmMedium = is_array($session) ? ($session['utm_medium'] ?? null) : null;
        $utmContent = is_array($session) ? ($session['utm_content'] ?? null) : null;
        $utmSource = $utmSource ?: ($cookie['utm_source'] ?? null);
        $utmMedium = $utmMedium ?: ($cookie['utm_medium'] ?? null);
        $utmContent = $utmContent ?: ($cookie['utm_content'] ?? null);

        $params = array_filter([
            'utm_source' => $utmSource,
            'utm_medium' => $utmMedium,
            'utm_campaign' => $campaignCode,
            'utm_content' => $utmContent,
            'fb' => $campaignCode,
        ], fn ($v) => filled($v));

        return $params === [] ? '' : '&'.http_build_query($params);
    }
}
