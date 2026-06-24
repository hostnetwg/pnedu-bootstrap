<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class MarketingCampaignLinkResolver
{
    public function resolveRedirectPath(string $campaignCode): ?string
    {
        return $this->resolveRedirectContext($campaignCode)['redirect_path'] ?? null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function resolveRedirectContext(string $campaignCode): ?array
    {
        $campaignCode = trim($campaignCode);
        if ($campaignCode === '') {
            return null;
        }

        $row = DB::connection('pneadm')
            ->table('marketing_campaigns as mc')
            ->leftJoin('marketing_source_types as mst', 'mst.id', '=', 'mc.source_type_id')
            ->where('mc.campaign_code', $campaignCode)
            ->whereNull('mc.deleted_at')
            ->whereNotNull('mc.course_id')
            ->select([
                'mc.id as campaign_id',
                'mc.course_id',
                'mc.landing_target',
                'mc.utm_medium',
                'mc.utm_content',
                'c.title as course_title',
                'mst.utm_source',
                'mst.default_utm_medium',
                'mst.default_utm_content',
                'mst.slug',
            ])
            ->leftJoin('courses as c', 'c.id', '=', 'mc.course_id')
            ->first();

        if (! $row) {
            return null;
        }

        $isOrderForm = ($row->landing_target ?? 'course_show') === 'order_form';
        $path = $isOrderForm
            ? '/courses/'.$row->course_id.'/order-form'
            : '/courses/'.$row->course_id;

        $utmSource = $this->resolveUtmSource($row);
        $utmMedium = $this->resolveUtmMedium($row);
        $utmContent = $this->resolveUtmContent($row);
        $query = [
            'utm_source' => $utmSource,
            'utm_medium' => $utmMedium,
            'utm_campaign' => $campaignCode,
        ];

        if (filled($utmContent)) {
            $query['utm_content'] = $utmContent;
        }

        return [
            'redirect_path' => $path.'?'.http_build_query($query),
            'campaign_id' => $row->campaign_id ? (int) $row->campaign_id : null,
            'campaign_code' => $campaignCode,
            'course_id' => (int) $row->course_id,
            'course_title_snapshot' => $row->course_title ? (string) $row->course_title : null,
            'landing_target' => $isOrderForm ? 'order_form_direct' : 'course_description',
            'campaign_channel' => $row->slug ? (string) $row->slug : $utmSource,
            'utm_source' => $utmSource,
            'utm_medium' => $utmMedium,
            'utm_campaign' => $campaignCode,
            'utm_content' => $utmContent,
        ];
    }

    /**
     * @param  object{utm_content: ?string, default_utm_content: ?string}  $row
     */
    private function resolveUtmContent(object $row): ?string
    {
        $content = trim((string) ($row->utm_content ?? ''));

        if ($content !== '') {
            return $content;
        }

        $default = trim((string) ($row->default_utm_content ?? ''));

        return $default !== '' ? $default : null;
    }

    /**
     * @param  object{utm_source: ?string, slug: ?string}  $row
     */
    private function resolveUtmSource(object $row): string
    {
        if (filled($row->utm_source)) {
            return (string) $row->utm_source;
        }

        return match ($row->slug ?? '') {
            'email' => 'newsletter',
            'website' => 'pnedu',
            'training' => 'webinar',
            default => filled($row->slug) ? (string) $row->slug : 'other',
        };
    }

    /**
     * @param  object{utm_medium: ?string, default_utm_medium: ?string}  $row
     */
    private function resolveUtmMedium(object $row): string
    {
        if (filled($row->utm_medium)) {
            return (string) $row->utm_medium;
        }

        return filled($row->default_utm_medium) ? (string) $row->default_utm_medium : 'paid';
    }
}
