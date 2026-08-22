<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\FormOrder;
use App\Models\FormOrderParticipant;
use App\Services\FormOrderOnlineAbandonmentService;
use App\Services\OrderFormParticipantService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FormOrderOnlineAbandonmentTest extends TestCase
{
    private ?int $createdFormOrderId = null;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            if (! Schema::connection('pneadm')->hasTable('form_orders')
                || ! Schema::connection('pneadm')->hasTable('form_order_participants')
                || ! Schema::connection('pneadm')->hasColumn('form_orders', 'cancelled_at')) {
                $this->markTestSkipped('Brak wymaganych tabel/kolumn form_orders w bazie pneadm.');
            }
        } catch (\Throwable) {
            $this->markTestSkipped('Brak połączenia z testową bazą pneadm.');
        }
    }

    protected function tearDown(): void
    {
        if ($this->createdFormOrderId) {
            FormOrderParticipant::query()
                ->where('form_order_id', $this->createdFormOrderId)
                ->forceDelete();
            FormOrder::withTrashed()->where('id', $this->createdFormOrderId)->forceDelete();
        }

        parent::tearDown();
    }

    public function test_cancelled_online_does_not_block_deferred_email(): void
    {
        $course = $this->course();
        $email = 'abandon-cancel-'.uniqid('', true).'@example.test';

        $this->createdFormOrderId = $this->createOnlineOrder($course->id, $email, FormOrder::PAYMENT_STATUS_CANCELLED);

        $service = app(OrderFormParticipantService::class);
        $service->assertEmailsAvailable($course->id, [
            ['first_name' => 'Anna', 'last_name' => 'Test', 'email' => $email],
        ]);

        $this->assertTrue(true);
    }

    public function test_recent_awaiting_online_still_blocks_email(): void
    {
        $course = $this->course();
        $email = 'abandon-await-'.uniqid('', true).'@example.test';

        $this->createdFormOrderId = $this->createOnlineOrder(
            $course->id,
            $email,
            FormOrder::PAYMENT_STATUS_AWAITING_PAYMENT,
            now('UTC')->subMinutes(15)
        );

        $this->expectException(ValidationException::class);

        app(OrderFormParticipantService::class)->assertEmailsAvailable($course->id, [
            ['first_name' => 'Anna', 'last_name' => 'Test', 'email' => $email],
        ]);
    }

    public function test_old_awaiting_online_does_not_block_email(): void
    {
        $course = $this->course();
        $email = 'abandon-old-'.uniqid('', true).'@example.test';

        $this->createdFormOrderId = $this->createOnlineOrder(
            $course->id,
            $email,
            FormOrder::PAYMENT_STATUS_AWAITING_PAYMENT,
            now('UTC')->subMinutes(90)
        );

        app(OrderFormParticipantService::class)->assertEmailsAvailable($course->id, [
            ['first_name' => 'Anna', 'last_name' => 'Test', 'email' => $email],
        ]);

        $this->assertTrue(true);
    }

    public function test_deferred_submit_auto_cancels_superseded_online_order(): void
    {
        $course = $this->course();
        $email = 'abandon-supersede-'.uniqid('', true).'@example.test';

        $this->createdFormOrderId = $this->createOnlineOrder(
            $course->id,
            $email,
            FormOrder::PAYMENT_STATUS_AWAITING_PAYMENT,
            now('UTC')->subMinutes(10)
        );

        $cancelled = app(FormOrderOnlineAbandonmentService::class)->cancelSupersededUnpaidOnlineOrders(
            $course->id,
            [$email]
        );

        $this->assertSame(1, $cancelled);

        $order = FormOrder::find($this->createdFormOrderId);
        $this->assertNotNull($order->cancelled_at);
        $this->assertSame('zastąpione zamówieniem odroczonym', $order->cancelled_reason);

        app(OrderFormParticipantService::class)->assertEmailsAvailable($course->id, [
            ['first_name' => 'Anna', 'last_name' => 'Test', 'email' => $email],
        ]);
    }

    public function test_deferred_order_in_progress_still_blocks_email(): void
    {
        $course = $this->course();
        $email = 'abandon-deferred-'.uniqid('', true).'@example.test';

        $order = FormOrder::create([
            'ident' => FormOrder::generateIdent(),
            'order_date' => now('UTC'),
            'product_id' => $course->id,
            'product_name' => 'Test course',
            'product_price' => 199,
            'payment_mode' => FormOrder::PAYMENT_MODE_DEFERRED_INVOICE,
            'payment_status' => FormOrder::PAYMENT_STATUS_SUBMITTED,
            'submission_source' => FormOrder::SUBMISSION_SOURCE_PNEDU_ORDER_FORM,
            'status_completed' => 0,
            'orderer_email' => 'buyer@example.test',
        ]);
        $this->createdFormOrderId = $order->id;

        FormOrderParticipant::create([
            'form_order_id' => $order->id,
            'participant_firstname' => 'Anna',
            'participant_lastname' => 'Test',
            'participant_email' => $email,
            'is_primary' => true,
        ]);

        $this->expectException(ValidationException::class);

        app(OrderFormParticipantService::class)->assertEmailsAvailable($course->id, [
            ['first_name' => 'Inna', 'last_name' => 'Osoba', 'email' => $email],
        ]);
    }

    private function course(): Course
    {
        $course = Course::query()->where('is_active', true)->orderByDesc('id')->first();
        if (! $course) {
            $this->markTestSkipped('Brak aktywnego kursu w bazie pneadm.');
        }

        return $course;
    }

    private function createOnlineOrder(
        int $courseId,
        string $email,
        string $paymentStatus,
        ?Carbon $orderDate = null
    ): int {
        $order = FormOrder::create([
            'ident' => FormOrder::generateIdent(),
            'order_date' => ($orderDate ?? now('UTC'))->utc(),
            'product_id' => $courseId,
            'product_name' => 'Test course',
            'product_price' => 199,
            'payment_mode' => FormOrder::PAYMENT_MODE_ONLINE_GATEWAY,
            'payment_status' => $paymentStatus,
            'submission_source' => FormOrder::SUBMISSION_SOURCE_PNEDU_ORDER_FORM,
            'status_completed' => 0,
            'orderer_email' => 'buyer@example.test',
        ]);

        FormOrderParticipant::create([
            'form_order_id' => $order->id,
            'participant_firstname' => 'Anna',
            'participant_lastname' => 'Test',
            'participant_email' => $email,
            'is_primary' => true,
        ]);

        return (int) $order->id;
    }
}
