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
    private ?string $secondTestCampaignCode = null;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        if (! $this->pneadmMarketingTablesAvailable()) {
            $this->markTestSkipped('Brak połączenia z bazą pneadm lub tabel marketingowych w środowisku testowym.');
        }

        $this->testCampaignCode = 't'.Str::lower(Str::random(8));
        $this->insertTestCampaign($this->testCampaignCode, $this->firstCourseId());
    }

    protected function tearDown(): void
    {
        foreach (array_filter([$this->testCampaignCode, $this->secondTestCampaignCode]) as $code) {
            MarketingCampaignStatsDaily::query()
                ->where('campaign_code', $code)
                ->delete();

            DB::connection('pneadm')->table('marketing_campaigns')
                ->where('campaign_code', $code)
                ->delete();
        }

        parent::tearDown();
    }

    public function test_same_visitor_counts_two_different_campaigns_on_same_day(): void
    {
        $this->secondTestCampaignCode = 't'.Str::lower(Str::random(8));
        $courseId = $this->firstCourseId();

        $this->insertTestCampaign($this->secondTestCampaignCode, $courseId);

        $this->get("/courses/{$courseId}?utm_campaign={$this->testCampaignCode}&utm_source=facebook&utm_medium=paid")
            ->assertOk();
        $this->get("/courses/{$courseId}?utm_campaign={$this->secondTestCampaignCode}&utm_source=facebook&utm_medium=paid")
            ->assertOk();

        $this->assertSame(1, $this->linkEntriesTotal($this->testCampaignCode));
        $this->assertSame(1, $this->linkEntriesTotal($this->secondTestCampaignCode));
    }

    public function test_funnel_opt_out_cookie_blocks_link_entry_tracking(): void
    {
        $courseId = $this->firstCourseId();

        $this->call(
            'GET',
            "/courses/{$courseId}?utm_campaign={$this->testCampaignCode}&utm_source=facebook&utm_medium=paid",
            [],
            ['pne_skip_funnel' => '1']
        )->assertOk();

        $this->assertSame(0, $this->linkEntriesTotal($this->testCampaignCode));
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

    private function linkEntriesTotal(?string $campaignCode = null): int
    {
        $campaignCode ??= $this->testCampaignCode;

        return (int) MarketingCampaignStatsDaily::query()
            ->where('campaign_code', $campaignCode)
            ->sum('link_entries');
    }

    private function insertTestCampaign(string $campaignCode, int $courseId): void
    {
        DB::connection('pneadm')->table('marketing_campaigns')->insert([
            'campaign_code' => $campaignCode,
            'name' => 'Test campaign link tracker',
            'source_type_id' => $this->firstSourceTypeId(),
            'course_id' => $courseId,
            'landing_target' => 'course_show',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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
