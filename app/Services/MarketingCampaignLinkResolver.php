<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class MarketingCampaignLinkResolver
{
    public function resolveRedirectPath(string $campaignCode): ?string
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
                'mc.course_id',
                'mc.landing_target',
                'mc.utm_medium',
                'mc.utm_content',
                'mst.utm_source',
                'mst.default_utm_medium',
                'mst.default_utm_content',
                'mst.slug',
            ])
            ->first();

        if (! $row) {
            return null;
        }

        $path = ($row->landing_target ?? 'course_show') === 'order_form'
            ? '/courses/'.$row->course_id.'/order-form'
            : '/courses/'.$row->course_id;

        $query = [
            'utm_source' => $this->resolveUtmSource($row),
            'utm_medium' => $this->resolveUtmMedium($row),
            'utm_campaign' => $campaignCode,
        ];

        if (filled($this->resolveUtmContent($row))) {
            $query['utm_content'] = $this->resolveUtmContent($row);
        }

        return $path.'?'.http_build_query($query);
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
