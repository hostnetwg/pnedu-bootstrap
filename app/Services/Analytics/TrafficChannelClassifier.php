<?php

namespace App\Services\Analytics;

class TrafficChannelClassifier
{
    /** @var list<string> */
    public const CHANNELS = [
        'newsletter',
        'paid_social',
        'organic_search',
        'direct',
        'referral',
        'internal_site',
        'paid_search',
        'organic_social',
        'unknown',
        'other',
    ];

    /**
     * @param  array{
     *     utm_source?: ?string,
     *     utm_medium?: ?string,
     *     utm_campaign?: ?string,
     *     utm_content?: ?string,
     *     utm_term?: ?string,
     *     referrer_domain?: ?string,
     *     fbclid_present?: bool,
     *     gclid_present?: bool,
     *     is_internal_referrer?: bool,
     *     conversion_placement?: ?string,
     * }  $signals
     * @return array{channel: string, source: ?string, medium: ?string, attribution_source: string}
     */
    public function classify(array $signals): array
    {
        $utmSource = $this->norm($signals['utm_source'] ?? null);
        $utmMedium = $this->norm($signals['utm_medium'] ?? null);
        $utmCampaign = $signals['utm_campaign'] ?? null;
        $referrerDomain = $this->normDomain($signals['referrer_domain'] ?? null);
        $fbclidPresent = (bool) ($signals['fbclid_present'] ?? false);
        $gclidPresent = (bool) ($signals['gclid_present'] ?? false);
        $isInternal = (bool) ($signals['is_internal_referrer'] ?? false);

        if ($isInternal || $this->isInternalHost($referrerDomain)) {
            return [
                'channel' => 'internal_site',
                'source' => 'pnedu',
                'medium' => 'internal',
                'attribution_source' => 'referrer',
            ];
        }

        if ($gclidPresent || $this->isPaidSearchUtm($utmSource, $utmMedium)) {
            return [
                'channel' => 'paid_search',
                'source' => $utmSource ?: 'google',
                'medium' => $utmMedium ?: 'cpc',
                'attribution_source' => $gclidPresent ? 'click_id' : 'utm',
            ];
        }

        if ($fbclidPresent || $this->isPaidSocialUtm($utmSource, $utmMedium) || $this->isPaidSocialReferrer($referrerDomain, $fbclidPresent)) {
            return [
                'channel' => 'paid_social',
                'source' => $utmSource ?: $this->socialSourceFromReferrer($referrerDomain) ?: 'facebook',
                'medium' => $utmMedium ?: 'paid_social',
                'attribution_source' => $fbclidPresent ? 'click_id' : 'utm',
            ];
        }

        if ($this->isNewsletterUtm($utmSource, $utmMedium)) {
            return [
                'channel' => 'newsletter',
                'source' => $utmSource ?: 'newsletter',
                'medium' => $utmMedium ?: 'email',
                'attribution_source' => 'utm',
            ];
        }

        if ($referrerDomain !== null && $this->isSearchEngine($referrerDomain)) {
            return [
                'channel' => 'organic_search',
                'source' => $this->searchSourceFromReferrer($referrerDomain),
                'medium' => 'organic',
                'attribution_source' => 'referrer',
            ];
        }

        if ($referrerDomain !== null && $this->isOrganicSocialReferrer($referrerDomain)) {
            return [
                'channel' => 'organic_social',
                'source' => $this->socialSourceFromReferrer($referrerDomain) ?: $referrerDomain,
                'medium' => 'social',
                'attribution_source' => 'referrer',
            ];
        }

        if ($this->isDirect($utmSource, $utmMedium, $utmCampaign, $referrerDomain, $fbclidPresent, $gclidPresent)) {
            return [
                'channel' => 'direct',
                'source' => 'direct',
                'medium' => 'none',
                'attribution_source' => 'none',
            ];
        }

        if ($referrerDomain !== null) {
            return [
                'channel' => 'referral',
                'source' => $referrerDomain,
                'medium' => 'referral',
                'attribution_source' => 'referrer',
            ];
        }

        if ($utmSource !== null || $utmMedium !== null) {
            return [
                'channel' => 'other',
                'source' => $utmSource,
                'medium' => $utmMedium,
                'attribution_source' => 'utm',
            ];
        }

        return [
            'channel' => 'unknown',
            'source' => null,
            'medium' => null,
            'attribution_source' => 'unknown',
        ];
    }

    public function isExternalChannel(string $channel): bool
    {
        return ! in_array($channel, ['internal_site', 'direct', 'unknown'], true);
    }

    private function isDirect(?string $utmSource, ?string $utmMedium, ?string $utmCampaign, ?string $referrerDomain, bool $fbclid, bool $gclid): bool
    {
        if ($fbclid || $gclid) {
            return false;
        }

        if ($referrerDomain !== null) {
            return false;
        }

        return $utmSource === null && $utmMedium === null && ($utmCampaign === null || trim((string) $utmCampaign) === '');
    }

    private function isNewsletterUtm(?string $source, ?string $medium): bool
    {
        if ($medium === null && $source === null) {
            return false;
        }

        $mediumHits = ['email', 'newsletter', 'mailing'];
        $sourceHits = ['newsletter', 'sendy', 'pne', 'pnedu', 'mail'];

        if ($medium !== null && in_array($medium, $mediumHits, true)) {
            return true;
        }

        if ($source !== null) {
            foreach ($sourceHits as $hit) {
                if (str_contains($source, $hit)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function isPaidSocialUtm(?string $source, ?string $medium): bool
    {
        if ($medium === null) {
            return false;
        }

        if (! in_array($medium, ['paid_social', 'cpc', 'paid', 'ppc', 'paidsocial'], true)) {
            return false;
        }

        if ($source === null) {
            return true;
        }

        return str_contains($source, 'facebook')
            || str_contains($source, 'meta')
            || str_contains($source, 'instagram')
            || str_contains($source, 'fb');
    }

    private function isPaidSearchUtm(?string $source, ?string $medium): bool
    {
        if ($medium === null) {
            return false;
        }

        if (! in_array($medium, ['cpc', 'ppc', 'paid_search', 'paidsearch'], true)) {
            return false;
        }

        return $source === null || str_contains($source, 'google') || str_contains($source, 'adwords');
    }

    private function isPaidSocialReferrer(?string $referrerDomain, bool $fbclidPresent): bool
    {
        return $fbclidPresent && $this->isOrganicSocialReferrer($referrerDomain);
    }

    private function isSearchEngine(?string $domain): bool
    {
        return $this->searchSourceFromReferrer($domain) !== 'other_search' || $this->matchesDomain($domain, [
            'google.', 'bing.com', 'duckduckgo.com', 'search.yahoo.com', 'yahoo.com', 'ecosia.org', 'yandex.', 'baidu.com',
        ]);
    }

    private function searchSourceFromReferrer(?string $domain): string
    {
        if ($domain === null) {
            return 'other_search';
        }

        return match (true) {
            str_contains($domain, 'google.') => 'google',
            str_contains($domain, 'bing.com') => 'bing',
            str_contains($domain, 'duckduckgo.com') => 'duckduckgo',
            str_contains($domain, 'yahoo.com') => 'yahoo',
            str_contains($domain, 'ecosia.org') => 'ecosia',
            str_contains($domain, 'yandex.') => 'yandex',
            default => 'other_search',
        };
    }

    private function isOrganicSocialReferrer(?string $domain): bool
    {
        return $this->matchesDomain($domain, [
            'facebook.com', 'fb.com', 'm.facebook.com', 'l.facebook.com', 'lm.facebook.com',
            'instagram.com', 'youtube.com', 'youtu.be', 'linkedin.com', 'lnkd.in',
            'twitter.com', 'x.com', 't.co', 'pinterest.com', 'tiktok.com',
        ]);
    }

    private function socialSourceFromReferrer(?string $domain): ?string
    {
        if ($domain === null) {
            return null;
        }

        if (str_contains($domain, 'facebook') || str_contains($domain, 'fb.com')) {
            return 'facebook';
        }
        if (str_contains($domain, 'instagram')) {
            return 'instagram';
        }
        if (str_contains($domain, 'youtube') || str_contains($domain, 'youtu.be')) {
            return 'youtube';
        }
        if (str_contains($domain, 'linkedin') || str_contains($domain, 'lnkd.in')) {
            return 'linkedin';
        }

        return $domain;
    }

    /**
     * @param  list<string>  $needles
     */
    private function matchesDomain(?string $domain, array $needles): bool
    {
        if ($domain === null) {
            return false;
        }

        foreach ($needles as $needle) {
            if ($domain === $needle || str_ends_with($domain, '.'.$needle) || str_contains($domain, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function isInternalHost(?string $domain): bool
    {
        if ($domain === null) {
            return false;
        }

        foreach ($this->internalHosts() as $host) {
            if ($domain === $host || str_ends_with($domain, '.'.$host)) {
                return true;
            }
        }

        return in_array($domain, ['localhost', '127.0.0.1'], true);
    }

    /**
     * @return list<string>
     */
    private function internalHosts(): array
    {
        $hosts = (array) config('analytics.traffic.internal_hosts', ['pnedu.pl', 'adm.pnedu.pl', 'localhost']);
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        if (is_string($appHost) && $appHost !== '') {
            $hosts[] = $appHost;
        }

        return array_values(array_unique(array_filter(array_map(
            fn (mixed $host): string => strtolower(trim((string) $host)),
            $hosts
        ))));
    }

    private function norm(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = strtolower(trim($value));

        return $value !== '' ? $value : null;
    }

    private function normDomain(?string $domain): ?string
    {
        $domain = $this->norm($domain);

        return $domain ? ltrim($domain, 'www.') : null;
    }
}
