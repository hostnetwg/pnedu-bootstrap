<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\FormOrder;
use App\Models\FormOrderParticipant;
use App\Models\OnlinePaymentOrder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FormOrderOnlinePaymentRecoveryInternalApiTest extends TestCase
{
    private ?int $createdFormOrderId = null;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.internal_api.token' => 'test-internal-token',
            'order_form.online_recovery_enabled' => true,
        ]);

        try {
            if (! Schema::connection('pneadm')->hasTable('form_orders')
                || ! Schema::connection('pneadm')->hasTable('online_payment_orders')
                || ! Schema::connection('pneadm')->hasColumn('form_orders', 'online_payment_recovery_sent_at')) {
                $this->markTestSkipped('Brak wymaganych tabel/kolumn w bazie pneadm.');
            }
        } catch (\Throwable) {
            $this->markTestSkipped('Brak połączenia z testową bazą pneadm.');
        }
    }

    protected function tearDown(): void
    {
        if ($this->createdFormOrderId) {
            OnlinePaymentOrder::query()->where('form_order_id', $this->createdFormOrderId)->delete();
            FormOrderParticipant::query()->where('form_order_id', $this->createdFormOrderId)->forceDelete();
            FormOrder::withTrashed()->where('id', $this->createdFormOrderId)->forceDelete();
        }

        parent::tearDown();
    }

    public function test_internal_api_sends_recovery_email(): void
    {
        Mail::fake();

        $course = Course::query()->where('is_active', true)->orderByDesc('id')->first();
        if (! $course) {
            $this->markTestSkipped('Brak aktywnego kursu.');
        }

        $formOrder = FormOrder::create([
            'ident' => FormOrder::generateIdent(),
            'order_date' => now('UTC')->subMinutes(90),
            'product_id' => $course->id,
            'product_name' => 'Test course',
            'product_price' => 199,
            'payment_mode' => FormOrder::PAYMENT_MODE_ONLINE_GATEWAY,
            'payment_status' => FormOrder::PAYMENT_STATUS_FAILED,
            'submission_source' => FormOrder::SUBMISSION_SOURCE_PNEDU_ORDER_FORM,
            'status_completed' => 0,
            'orderer_email' => 'buyer@example.test',
        ]);
        $this->createdFormOrderId = $formOrder->id;

        FormOrderParticipant::create([
            'form_order_id' => $formOrder->id,
            'participant_firstname' => 'Anna',
            'participant_lastname' => 'Test',
            'participant_email' => 'participant@example.test',
            'is_primary' => true,
        ]);

        OnlinePaymentOrder::create([
            'form_order_id' => $formOrder->id,
            'ident' => OnlinePaymentOrder::generateIdent(),
            'course_id' => $course->id,
            'payment_gateway' => 'payu',
            'status' => OnlinePaymentOrder::STATUS_FAILED,
            'total_amount' => 199,
            'currency' => 'PLN',
            'buyer_type' => 'person',
            'email' => 'participant@example.test',
            'first_name' => 'Anna',
            'last_name' => 'Test',
            'phone' => '500600700',
        ]);

        $response = $this->postJson(
            '/api/internal/form-orders/'.$formOrder->id.'/send-online-payment-recovery',
            ['allow_resend' => true],
            ['Authorization' => 'Bearer test-internal-token']
        );

        $response->assertOk()->assertJson(['success' => true]);
        Mail::assertSent(\App\Mail\OnlinePaymentRecoveryMail::class);

        $formOrder->refresh();
        $this->assertNotNull($formOrder->online_payment_recovery_sent_at);
    }

    public function test_internal_api_previews_recovery_email(): void
    {
        $course = Course::query()->where('is_active', true)->orderByDesc('id')->first();
        if (! $course) {
            $this->markTestSkipped('Brak aktywnego kursu.');
        }

        $formOrder = FormOrder::create([
            'ident' => FormOrder::generateIdent(),
            'order_date' => now('UTC')->subMinutes(90),
            'product_id' => $course->id,
            'product_name' => 'Test course preview',
            'product_price' => 199,
            'payment_mode' => FormOrder::PAYMENT_MODE_ONLINE_GATEWAY,
            'payment_status' => FormOrder::PAYMENT_STATUS_FAILED,
            'submission_source' => FormOrder::SUBMISSION_SOURCE_PNEDU_ORDER_FORM,
            'status_completed' => 0,
            'orderer_email' => 'buyer-preview@example.test',
        ]);
        $this->createdFormOrderId = $formOrder->id;

        FormOrderParticipant::create([
            'form_order_id' => $formOrder->id,
            'participant_firstname' => 'Anna',
            'participant_lastname' => 'Test',
            'participant_email' => 'participant-preview@example.test',
            'is_primary' => true,
        ]);

        OnlinePaymentOrder::create([
            'form_order_id' => $formOrder->id,
            'ident' => OnlinePaymentOrder::generateIdent(),
            'course_id' => $course->id,
            'payment_gateway' => 'payu',
            'status' => OnlinePaymentOrder::STATUS_FAILED,
            'total_amount' => 199,
            'currency' => 'PLN',
            'buyer_type' => 'person',
            'email' => 'participant-preview@example.test',
            'first_name' => 'Anna',
            'last_name' => 'Test',
            'phone' => '500600700',
        ]);

        $response = $this->getJson(
            '/api/internal/form-orders/'.$formOrder->id.'/preview-online-payment-recovery',
            ['Authorization' => 'Bearer test-internal-token']
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('to', 'buyer-preview@example.test, participant-preview@example.test');
        $this->assertStringContainsString('Przypomnienie o płatności', (string) $response->json('subject'));
        $this->assertStringContainsString('Zapłać ponownie', (string) $response->json('body_html'));
        $this->assertNull($formOrder->fresh()->online_payment_recovery_sent_at);
    }
}
