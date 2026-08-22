<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\FormOrder;
use App\Models\FormOrderParticipant;
use App\Models\OnlinePaymentOrder;
use App\Services\FormOrderOnlinePaymentRetryService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class FormOrderOnlinePaymentRetryTest extends TestCase
{
    private ?int $createdFormOrderId = null;

    private ?int $createdOnlinePaymentOrderId = null;

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

    public function test_pending_page_shows_retry_actions_for_linked_form_order(): void
    {
        $course = $this->course();
        [$formOrder, $onlineOrder] = $this->createLinkedOrders($course->id);

        $response = $this->get(route('payment.pending', $onlineOrder->ident));

        $response->assertOk();
        $response->assertSee('Zapłać ponownie', false);
        $response->assertSee('Wolę fakturę z odroczonym terminem płatności', false);
        $response->assertSee($formOrder->ident, false);
    }

    public function test_signed_retry_route_creates_new_online_payment_attempt(): void
    {
        $course = $this->course();
        [$formOrder, $onlineOrder] = $this->createLinkedOrders($course->id);

        $retryService = $this->mock(FormOrderOnlinePaymentRetryService::class);
        $retryService->shouldReceive('canRetryPayment')->once()->andReturn(true);
        $retryService->shouldReceive('createRetryPaymentAttempt')
            ->once()
            ->andReturnUsing(function () use ($formOrder, $course) {
                $newAttempt = OnlinePaymentOrder::create([
                    'form_order_id' => $formOrder->id,
                    'ident' => OnlinePaymentOrder::generateIdent(),
                    'course_id' => $course->id,
                    'payment_gateway' => 'payu',
                    'status' => OnlinePaymentOrder::STATUS_PENDING,
                    'total_amount' => 199,
                    'currency' => 'PLN',
                    'buyer_type' => 'person',
                    'email' => 'participant@example.test',
                    'first_name' => 'Anna',
                    'last_name' => 'Test',
                    'phone' => '500600700',
                ]);
                $this->createdOnlinePaymentOrderId = $newAttempt->id;

                return $newAttempt;
            });
        $retryService->shouldReceive('sendPaymentStartedMail')->once();
        $retryService->shouldReceive('redirectToGateway')
            ->once()
            ->andReturn(redirect()->route('payment.pending', $onlineOrder->ident));

        $url = URL::temporarySignedRoute('orders.retry-payment', now()->addHour(), [
            'ident' => $formOrder->ident,
        ]);

        $response = $this->get($url);

        $response->assertRedirect(route('payment.pending', $onlineOrder->ident));
    }

    /**
     * @return array{0: FormOrder, 1: OnlinePaymentOrder}
     */
    private function createLinkedOrders(int $courseId): array
    {
        $formOrder = FormOrder::create([
            'ident' => FormOrder::generateIdent(),
            'order_date' => now('UTC'),
            'product_id' => $courseId,
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

        $onlineOrder = OnlinePaymentOrder::create([
            'form_order_id' => $formOrder->id,
            'ident' => OnlinePaymentOrder::generateIdent(),
            'course_id' => $courseId,
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
        $this->createdOnlinePaymentOrderId = $onlineOrder->id;

        return [$formOrder, $onlineOrder];
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
