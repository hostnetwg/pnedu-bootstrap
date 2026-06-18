<?php

namespace Tests\Feature;

use App\Models\MarketingCampaignStatsDaily;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class MarketingCampaignLinkTrackerTest extends TestCase
{
    private ?string $testCampaignCode = null;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        if (! $this->pneadmMarketingTablesAvailable()) {
            $this->markTestSkipped('Brak połączenia z bazą pneadm lub tabel marketingowych w środowisku testowym.');
        }

        $this->testCampaignCode = 't'.Str::lower(Str::random(8));

        DB::connection('pneadm')->table('marketing_campaigns')->insert([
            'campaign_code' => $this->testCampaignCode,
            'name' => 'Test campaign link tracker',
            'source_type_id' => $this->firstSourceTypeId(),
            'course_id' => $this->firstCourseId(),
            'landing_target' => 'course_show',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        if ($this->testCampaignCode !== null) {
            MarketingCampaignStatsDaily::query()
                ->where('campaign_code', $this->testCampaignCode)
                ->delete();

            DB::connection('pneadm')->table('marketing_campaigns')
                ->where('campaign_code', $this->testCampaignCode)
                ->delete();
        }

        parent::tearDown();
    }

    public function test_utm_campaign_in_query_increments_link_entry_once_per_day(): void
    {
        $courseId = $this->firstCourseId();

        $this->get("/courses/{$courseId}?utm_campaign={$this->testCampaignCode}&utm_source=facebook&utm_medium=paid")
            ->assertOk();

        $this->assertSame(1, $this->linkEntriesTotal());

        $this->get("/courses/{$courseId}?utm_campaign={$this->testCampaignCode}&utm_source=facebook&utm_medium=paid")
            ->assertOk();

        $this->assertSame(1, $this->linkEntriesTotal());
    }

    public function test_short_link_increments_without_double_count_on_redirect_landing(): void
    {
        $this->get('/l/'.$this->testCampaignCode)->assertRedirect();

        $this->assertSame(1, $this->linkEntriesTotal());
    }

    public function test_unknown_campaign_code_is_not_counted(): void
    {
        $courseId = $this->firstCourseId();

        $this->get("/courses/{$courseId}?utm_campaign=__missing_code__")->assertOk();

        $this->assertSame(0, MarketingCampaignStatsDaily::query()
            ->where('campaign_code', '__missing_code__')
            ->sum('link_entries'));
    }

    private function linkEntriesTotal(): int
    {
        return (int) MarketingCampaignStatsDaily::query()
            ->where('campaign_code', $this->testCampaignCode)
            ->sum('link_entries');
    }

    private function pneadmMarketingTablesAvailable(): bool
    {
        try {
            return Schema::connection('pneadm')->hasTable('marketing_campaigns')
                && Schema::connection('pneadm')->hasTable('marketing_campaign_stats_daily')
                && Schema::connection('pneadm')->hasTable('marketing_source_types')
                && Schema::connection('pneadm')->hasTable('courses');
        } catch (\Throwable) {
            return false;
        }
    }

    private function firstSourceTypeId(): int
    {
        return (int) DB::connection('pneadm')->table('marketing_source_types')->orderBy('id')->value('id');
    }

    private function firstCourseId(): int
    {
        return (int) DB::connection('pneadm')->table('courses')->orderBy('id')->value('id');
    }
}
