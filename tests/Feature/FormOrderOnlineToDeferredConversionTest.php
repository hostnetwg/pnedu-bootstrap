<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\FormOrder;
use App\Models\FormOrderParticipant;
use App\Models\OnlinePaymentOrder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class FormOrderOnlineToDeferredConversionTest extends TestCase
{
    private ?int $createdFormOrderId = null;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            if (! Schema::connection('pneadm')->hasTable('form_orders')
                || ! Schema::connection('pneadm')->hasTable('online_payment_orders')
                || ! Schema::connection('pneadm')->hasTable('form_order_participants')) {
                $this->markTestSkipped('Brak wymaganych tabel w bazie pneadm.');
            }
        } catch (\Throwable) {
            $this->markTestSkipped('Brak połączenia z testową bazą pneadm.');
        }
    }

    protected function tearDown(): void
    {
        if ($this->createdFormOrderId) {
            OnlinePaymentOrder::query()
                ->where('form_order_id', $this->createdFormOrderId)
                ->delete();
            FormOrderParticipant::query()
                ->where('form_order_id', $this->createdFormOrderId)
                ->forceDelete();
            FormOrder::withTrashed()->where('id', $this->createdFormOrderId)->forceDelete();
        }

        parent::tearDown();
    }

    public function test_confirm_page_shows_order_summary_and_cta(): void
    {
        $course = $this->course();
        $formOrder = $this->createUnpaidOnlineOrder($course->id);

        $url = URL::temporarySignedRoute('orders.convert-to-deferred', now()->addHour(), [
            'ident' => $formOrder->ident,
        ]);

        $response = $this->get($url);

        $response->assertOk();
        $response->assertSee($formOrder->ident, false);
        $response->assertSee('Potwierdzam zamówienie z fakturą z odroczonym terminem', false);
        $response->assertSee('Chcę poprawić dane', false);
        $response->assertSee('Termin płatności', false);
        $response->assertSee('Anna Test', false);
        if (! empty($course->trainer) && $course->trainer !== 'Brak trenera') {
            $response->assertSee($course->trainer_title.':', false);
            $response->assertSee($course->trainer, false);
        }
    }

    public function test_confirm_converts_same_form_order_to_deferred(): void
    {
        $course = $this->course();
        $formOrder = $this->createUnpaidOnlineOrder($course->id);

        $url = URL::temporarySignedRoute('orders.convert-to-deferred.confirm', now()->addHour(), [
            'ident' => $formOrder->ident,
        ]);

        $response = $this->post($url, [
            'payment_terms' => 14,
        ]);

        $response->assertRedirect(route('orders.summary', $formOrder->ident));
        $response->assertSessionHas('order_just_submitted', $formOrder->ident);

        $formOrder->refresh();
        $this->assertSame(FormOrder::PAYMENT_MODE_DEFERRED_INVOICE, $formOrder->payment_mode);
        $this->assertSame(FormOrder::PAYMENT_STATUS_SUBMITTED, $formOrder->payment_status);
        $this->assertSame(14, (int) $formOrder->invoice_payment_delay);
    }

    public function test_payment_terms_cannot_exceed_max(): void
    {
        $course = $this->course();
        $formOrder = $this->createUnpaidOnlineOrder($course->id);

        $url = URL::temporarySignedRoute('orders.convert-to-deferred.confirm', now()->addHour(), [
            'ident' => $formOrder->ident,
        ]);

        $response = $this->from(
            URL::temporarySignedRoute('orders.convert-to-deferred', now()->addHour(), [
                'ident' => $formOrder->ident,
            ])
        )->post($url, [
            'payment_terms' => 45,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('payment_terms');

        $formOrder->refresh();
        $this->assertSame(FormOrder::PAYMENT_MODE_ONLINE_GATEWAY, $formOrder->payment_mode);
    }

    private function createUnpaidOnlineOrder(int $courseId): FormOrder
    {
        $formOrder = FormOrder::create([
            'ident' => FormOrder::generateIdent(),
            'order_date' => now('UTC'),
            'product_id' => $courseId,
            'product_name' => 'Test course convert',
            'product_price' => 250,
            'payment_mode' => FormOrder::PAYMENT_MODE_ONLINE_GATEWAY,
            'payment_status' => FormOrder::PAYMENT_STATUS_FAILED,
            'submission_source' => FormOrder::SUBMISSION_SOURCE_PNEDU_ORDER_FORM,
            'status_completed' => 0,
            'buyer_name' => 'Jan Kowalski',
            'orderer_name' => 'Jan Kowalski',
            'orderer_email' => 'buyer-convert@example.test',
            'orderer_phone' => '501502503',
        ]);
        $this->createdFormOrderId = $formOrder->id;

        FormOrderParticipant::create([
            'form_order_id' => $formOrder->id,
            'participant_firstname' => 'Anna',
            'participant_lastname' => 'Test',
            'participant_email' => 'participant-convert@example.test',
            'is_primary' => true,
        ]);

        OnlinePaymentOrder::create([
            'form_order_id' => $formOrder->id,
            'ident' => OnlinePaymentOrder::generateIdent(),
            'course_id' => $courseId,
            'payment_gateway' => 'payu',
            'status' => OnlinePaymentOrder::STATUS_FAILED,
            'total_amount' => 250,
            'currency' => 'PLN',
            'buyer_type' => 'person',
            'email' => 'participant-convert@example.test',
            'first_name' => 'Anna',
            'last_name' => 'Test',
            'phone' => '501502503',
        ]);

        return $formOrder;
    }

    private function course(): Course
    {
        $course = Course::query()->where('is_active', true)->orderByDesc('id')->first();
        if (! $course) {
            $this->markTestSkipped('Brak aktywnego kursu w bazie pneadm.');
        }

        return $course;
    }
}
