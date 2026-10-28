<?php

namespace Tests\Feature;

use App\Models\FormOrder;
use App\Services\OrderCampaignAttributionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class OperationalCampaignAttributionTest extends TestCase
{
    private string $campaignCode;

    private string $emailPrefix = 'operational-campaign-attribution';

    private int $courseId;

    private ?int $priceVariantId = null;

    private ?int $temporaryCourseId = null;

    private array $temporaryPriceVariantIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Queue::fake();
        Mail::fake();

        config([
            'consent.required' => true,
            'consent.first_party_operational_without_analytics_consent' => true,
        ]);

        if (! $this->requiredTablesAvailable()) {
            $this->markTestSkipped('Brak wymaganych tabel pneadm w środowisku testowym.');
        }

        [$this->courseId, $this->priceVariantId] = $this->createTestCourse();
        $this->campaignCode = 'test-order-attr-'.Str::lower(Str::random(8));

        DB::connection('pneadm')->table('marketing_campaigns')->insert([
            'campaign_code' => $this->campaignCode,
            'name' => 'Operational campaign attribution test',
            'source_type_id' => $this->firstSourceTypeId(),
            'course_id' => $this->courseId,
            'landing_target' => 'order_form',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        $orderIds = FormOrder::withTrashed()
            ->where('orderer_email', 'like', $this->emailPrefix.'%')
            ->pluck('id');

        if ($orderIds->isNotEmpty()) {
            DB::connection('pneadm')->table('online_payment_orders')
                ->whereIn('form_order_id', $orderIds)
                ->delete();
            DB::connection('pneadm')->table('form_order_participants')
                ->whereIn('form_order_id', $orderIds)
                ->delete();
            FormOrder::withTrashed()->whereIn('id', $orderIds)->forceDelete();
        }

        if (isset($this->campaignCode)) {
            DB::connection('pneadm')->table('marketing_campaign_stats_daily')
                ->where('campaign_code', $this->campaignCode)
                ->delete();
            DB::connection('pneadm')->table('marketing_campaigns')
                ->where('campaign_code', $this->campaignCode)
                ->delete();
        }

        if ($this->temporaryPriceVariantIds !== []) {
            DB::connection('pneadm')->table('course_price_variants')
                ->whereIn('id', $this->temporaryPriceVariantIds)
                ->delete();
        }

        if ($this->temporaryCourseId !== null) {
            DB::connection('pneadm')->table('courses')
                ->where('id', $this->temporaryCourseId)
                ->delete();
        }

        parent::tearDown();
    }

    public function test_full_utm_without_analytics_consent_is_saved_for_deferred_order(): void
    {
        $url = route('payment.order-form', $this->courseId).'?'.http_build_query([
            'utm_source' => 'newsletter',
            'utm_medium' => 'email',
            'utm_campaign' => $this->campaignCode,
        ]);

        $this->get($url)
            ->assertOk()
            ->assertCookieMissing('pne_marketing')
            ->assertSee('name="fb_source" value="'.$this->campaignCode.'"', false);

        $payload = $this->validDeferredPayload();

        $this->post(route('payment.order-form.store', $this->courseId), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('form_orders', [
            'orderer_email' => $payload['contact_email'],
            'payment_mode' => FormOrder::PAYMENT_MODE_DEFERRED_INVOICE,
            'fb_source' => $this->campaignCode,
        ], 'pneadm');
    }

    public function test_short_link_without_analytics_consent_is_saved_for_payu_order(): void
    {
        $redirect = $this->get(route('marketing.short-link', $this->campaignCode))
            ->assertRedirect();

        $landingUrl = (string) $redirect->headers->get('Location');
        $this->assertStringContainsString('utm_campaign='.urlencode($this->campaignCode), $landingUrl);

        $this->get($landingUrl)
            ->assertOk()
            ->assertCookieMissing('pne_marketing')
            ->assertSee('name="fb_source" value="'.$this->campaignCode.'"', false);

        $this->fakePayuGateway();
        $payload = $this->validOnlinePayload('payu');

        $this->post(route('payment.order-form.store', $this->courseId), $payload)
            ->assertRedirect('https://payu.example.test/payment');

        $order = FormOrder::query()->where('orderer_email', $payload['contact_email'])->firstOrFail();

        $this->assertSame($this->campaignCode, $order->fb_source);
        $this->assertDatabaseHas('online_payment_orders', [
            'form_order_id' => $order->id,
            'payment_gateway' => 'payu',
        ], 'pneadm');
    }

    public function test_campaign_session_from_course_description_is_saved_for_paynow_order(): void
    {
        $descriptionUrl = route('courses.show', $this->courseId).'?'.http_build_query([
            'utm_source' => 'newsletter',
            'utm_medium' => 'email',
            'utm_campaign' => $this->campaignCode,
        ]);

        $this->get($descriptionUrl)
            ->assertOk()
            ->assertCookieMissing('pne_marketing');

        $this->get(route('payment.order-form', $this->courseId))
            ->assertOk()
            ->assertSee('name="fb_source" value="'.$this->campaignCode.'"', false);

        $this->fakePaynowGateway();
        $payload = $this->validOnlinePayload('paynow');

        $this->post(route('payment.order-form.store', $this->courseId), $payload)
            ->assertRedirect('https://paynow.example.test/payment');

        $order = FormOrder::query()->where('orderer_email', $payload['contact_email'])->firstOrFail();

        $this->assertSame($this->campaignCode, $order->fb_source);
        $this->assertDatabaseHas('online_payment_orders', [
            'form_order_id' => $order->id,
            'payment_gateway' => 'paynow',
            'payu_order_id' => 'PAYNOW-TEST',
        ], 'pneadm');
    }

    public function test_operational_attribution_does_not_enable_analytics_attribution(): void
    {
        $url = route('payment.order-form', $this->courseId).'?utm_campaign='.$this->campaignCode;

        $this->get($url)
            ->assertOk()
            ->assertCookieMissing('pne_marketing');

        $this->assertSame(
            $this->campaignCode,
            session(OrderCampaignAttributionService::SESSION_KEY.'.campaign_code')
        );
        $this->assertNull(session('marketing.attribution.campaign_code'));
    }

    private function validOnlinePayload(string $gateway): array
    {
        $payload = $this->validDeferredPayload();
        $payload['price_variant_id'] = $this->ensurePositivePriceVariant();
        $payload['payment_type'] = 'online';
        $payload['payment_gateway'] = $gateway;
        unset($payload['payment_terms']);

        return $payload;
    }

    private function validDeferredPayload(): array
    {
        $email = $this->emailPrefix.'-'.Str::lower(Str::random(8)).'@example.test';

        $payload = [
            'buyer_type' => 'person',
            'payment_type' => 'deferred',
            'contact_name' => 'Jan Kowalski',
            'contact_phone' => '501654274',
            'contact_email' => $email,
            'buyer_address' => 'Testowa 1',
            'buyer_postcode' => '00-001',
            'buyer_city' => 'Warszawa',
            'buyer_person_first_name' => 'Jan',
            'buyer_person_last_name' => 'Kowalski',
            'participant_first_name' => 'Anna',
            'participant_last_name' => 'Nowak',
            'participant_email' => $email,
            'invoice_notes' => null,
            'payment_terms' => 14,
        ];

        if ($this->priceVariantId !== null) {
            $payload['price_variant_id'] = $this->priceVariantId;
        }

        return $payload;
    }

    private function fakePayuGateway(): void
    {
        Http::fake([
            '*/pl/standard/user/oauth/authorize' => Http::response(['access_token' => 'fake-token'], 200),
            '*/api/v2_1/orders' => Http::response([
                'redirectUri' => 'https://payu.example.test/payment',
                'orderId' => 'PAYU-TEST',
            ], 201),
        ]);

        config([
            'services.payu.client_id' => 'test-client',
            'services.payu.client_secret' => 'test-secret',
            'services.payu.pos_id' => 'test-pos',
        ]);
    }

    private function fakePaynowGateway(): void
    {
        Http::fake([
            'https://api.paynow.pl/v3/payments' => Http::response([
                'redirectUrl' => 'https://paynow.example.test/payment',
                'paymentId' => 'PAYNOW-TEST',
            ], 201),
        ]);

        config([
            'services.paynow.sandbox' => false,
            'services.paynow.api_key' => 'test-api-key',
            'services.paynow.signature_key' => 'test-signature-key',
        ]);
    }

    private function createTestCourse(): array
    {
        $this->temporaryCourseId = (int) DB::connection('pneadm')->table('courses')->insertGetId([
            'title' => 'Test atrybucji kampanii '.Str::random(6),
            'description' => 'Testowe szkolenie',
            'start_date' => now()->addDays(30),
            'end_date' => now()->addDays(30)->addHours(2),
            'is_paid' => true,
            'type' => 'online',
            'category' => 'open',
            'is_active' => true,
            'show_on_pnedu' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$this->temporaryCourseId, null];
    }

    private function ensurePositivePriceVariant(): int
    {
        $variantId = DB::connection('pneadm')
            ->table('course_price_variants')
            ->where('course_id', $this->courseId)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->where('price', '>', 0)
            ->orderBy('id')
            ->value('id');

        if ($variantId !== null) {
            return (int) $variantId;
        }

        $id = DB::connection('pneadm')->table('course_price_variants')->insertGetId([
            'course_id' => $this->courseId,
            'name' => 'Operational attribution test price',
            'description' => null,
            'is_active' => true,
            'price' => 199.00,
            'is_promotion' => false,
            'promotion_price' => null,
            'promotion_type' => 'disabled',
            'promotion_start' => null,
            'promotion_end' => null,
            'access_type' => '1',
            'access_start_datetime' => null,
            'access_end_datetime' => null,
            'access_duration_value' => null,
            'access_duration_unit' => null,
            'availability_after_course_end' => 'always',
            'post_end_access_rule' => 'inherit',
            'post_end_access_duration_value' => null,
            'post_end_access_duration_unit' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->temporaryPriceVariantIds[] = $id;

        return (int) $id;
    }

    private function firstSourceTypeId(): int
    {
        return (int) DB::connection('pneadm')
            ->table('marketing_source_types')
            ->orderBy('id')
            ->value('id');
    }

    private function requiredTablesAvailable(): bool
    {
        try {
            return Schema::connection('pneadm')->hasTable('courses')
                && Schema::connection('pneadm')->hasTable('course_price_variants')
                && Schema::connection('pneadm')->hasTable('form_orders')
                && Schema::connection('pneadm')->hasTable('form_order_participants')
                && Schema::connection('pneadm')->hasTable('online_payment_orders')
                && Schema::connection('pneadm')->hasTable('marketing_campaigns')
                && Schema::connection('pneadm')->hasTable('marketing_campaign_stats_daily')
                && Schema::connection('pneadm')->hasTable('marketing_source_types');
        } catch (\Throwable) {
            return false;
        }
    }
}
