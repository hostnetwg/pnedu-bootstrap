<?php

namespace App\Services;

use App\Models\MarketingCampaignStatsDaily;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MarketingCampaignLinkTracker
{
    public function __construct(
        private readonly FunnelSkipService $funnelSkip,
    ) {}

    public function trackFromRequest(Request $request): void
    {
        if (! $this->shouldTrack($request)) {
            return;
        }

        $campaignCode = $this->campaignCodeFromQuery($request);
        if ($campaignCode === null) {
            return;
        }

        $this->incrementOnce($request, $campaignCode);
    }

    public function trackCampaignCode(Request $request, string $campaignCode): void
    {
        if (! $this->shouldTrack($request)) {
            return;
        }

        $campaignCode = Str::limit(trim($campaignCode), 64, '');
        if ($campaignCode === '' || ! $this->campaignExists($campaignCode)) {
            return;
        }

        $this->incrementOnce($request, $campaignCode);
    }

    private function shouldTrack(Request $request): bool
    {
        if ($this->funnelSkip->shouldSkipTracking($request)) {
            return false;
        }

        if (! $request->isMethod('GET')) {
            return false;
        }

        if ($request->ajax() || $request->prefetch() || $request->header('Purpose') === 'prefetch') {
            return false;
        }

        $ua = strtolower((string) $request->userAgent());
        if ($ua === '' || str_contains($ua, 'bot') || str_contains($ua, 'spider') || str_contains($ua, 'crawl')) {
            return false;
        }

        return true;
    }

    private function campaignCodeFromQuery(Request $request): ?string
    {
        $raw = $request->query('utm_campaign', $request->query('fb', $request->query('fb_source')));
        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        $campaignCode = Str::limit(trim($raw), 64, '');
        if ($campaignCode === '' || ! $this->campaignExists($campaignCode)) {
            return null;
        }

        return $campaignCode;
    }

    private function incrementOnce(Request $request, string $campaignCode): void
    {
        $sid = $this->visitorSid($request);
        $date = now()->toDateString();
        $cacheKey = "campaign:link:{$sid}:{$campaignCode}:{$date}";

        if (! Cache::add($cacheKey, 1, now()->endOfDay())) {
            return;
        }

        $stat = MarketingCampaignStatsDaily::query()->firstOrCreate(
            ['campaign_code' => $campaignCode, 'stat_date' => $date],
            ['link_entries' => 0],
        );

        $stat->increment('link_entries');
    }

    private function visitorSid(Request $request): string
    {
        $cookieName = (string) config('marketing.funnel_session_cookie', 'pne_funnel_sid');
        $sid = $request->cookie($cookieName);
        if (is_string($sid) && $sid !== '') {
            return $sid;
        }

        return 'ip:'.hash('sha256', (string) $request->ip().'|'.(string) $request->userAgent());
    }

    private function campaignExists(string $campaignCode): bool
    {
        return DB::connection('pneadm')
            ->table('marketing_campaigns')
            ->where('campaign_code', $campaignCode)
            ->whereNull('deleted_at')
            ->exists();
    }
}
