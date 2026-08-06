<?php

namespace Tests\Unit;

use App\Models\FormOrder;
use App\Models\FormOrderParticipant;
use App\Services\FormOrderCheckoutResumeService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FormOrderCheckoutResumeServiceTest extends TestCase
{
    private array $createdOrderIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    protected function tearDown(): void
    {
        if ($this->createdOrderIds !== [] && $this->requiredTablesAvailable()) {
            DB::connection('pneadm')
                ->table('form_order_participants')
                ->whereIn('form_order_id', $this->createdOrderIds)
                ->delete();

            FormOrder::withTrashed()
                ->whereIn('id', $this->createdOrderIds)
                ->forceDelete();
        }

        parent::tearDown();
    }

    public function test_normalize_participant_email_trims_and_lowercases(): void
    {
        $service = new FormOrderCheckoutResumeService;

        $this->assertSame('jan.kowalski@szkola.pl', $service->normalizeParticipantEmail('  Jan.Kowalski@Szkola.pl  '));
    }

    public function test_merge_resume_skips_when_edit_mode(): void
    {
        $service = new FormOrderCheckoutResumeService;

        $result = $service->mergeResumeIntoFormData(42, ['buyer_name' => 'Test'], true);

        $this->assertSame(['buyer_name' => 'Test'], $result);
    }

    public function test_store_and_read_session_payload(): void
    {
        $service = new FormOrderCheckoutResumeService;

        $service->storeAfterSubmit(99, new \App\Models\FormOrder([
            'ident' => '260618-TEST01',
        ]), 'nauczyciel@szkola.pl');

        $payload = $service->readSessionPayload();

        $this->assertIsArray($payload);
        $this->assertSame(99, $payload['course_id']);
        $this->assertSame('260618-TEST01', $payload['ident']);
        $this->assertSame('nauczyciel@szkola.pl', $payload['participant_email']);
    }

    public function test_clear_resume_for_course_only_matching_course(): void
    {
        $service = new FormOrderCheckoutResumeService;

        $service->storeAfterSubmit(10, new \App\Models\FormOrder(['ident' => 'A']), 'a@test.pl');
        $service->clearResumeForCourse(99);

        $this->assertNotNull($service->readSessionPayload());

        $service->clearResumeForCourse(10);

        $this->assertNull($service->readSessionPayload());
    }

    public function test_merge_resume_does_not_inject_for_other_course(): void
    {
        $service = new FormOrderCheckoutResumeService;

        session([
            FormOrderCheckoutResumeService::SESSION_KEY => [
                'course_id' => 1,
                'ident' => '260618-ABC123',
                'participant_email' => 'teacher@school.pl',
            ],
        ]);

        $result = $service->mergeResumeIntoFormData(5, [], false);

        $this->assertArrayNotHasKey('order_ident', $result);
    }

    public function test_explicit_edit_intent_resolves_order_when_participant_email_changes(): void
    {
        $this->skipWhenPneadmTablesAreUnavailable();

        $courseId = $this->courseId();
        $order = $this->createEditableOrder($courseId, 'old-participant@example.test');

        $resolved = (new FormOrderCheckoutResumeService)->resolveForSubmit(
            $courseId,
            $order->ident,
            'new-participant@example.test',
            true
        );

        $this->assertNotNull($resolved);
        $this->assertSame($order->id, $resolved->id);
    }

    public function test_resume_without_edit_intent_keeps_email_match_guard_even_with_order_ident(): void
    {
        $this->skipWhenPneadmTablesAreUnavailable();

        $courseId = $this->courseId();
        $order = $this->createEditableOrder($courseId, 'old-participant@example.test');

        session([
            FormOrderCheckoutResumeService::SESSION_KEY => [
                'course_id' => $courseId,
                'ident' => $order->ident,
                'participant_email' => 'old-participant@example.test',
            ],
        ]);

        $resolved = (new FormOrderCheckoutResumeService)->resolveForSubmit(
            $courseId,
            $order->ident,
            'new-participant@example.test',
            false
        );

        $this->assertNull($resolved);
    }

    private function createEditableOrder(int $courseId, string $participantEmail): FormOrder
    {
        $order = FormOrder::create([
            'ident' => FormOrder::generateIdent(),
            'ptw' => 14,
            'order_date' => now('UTC'),
            'product_id' => $courseId,
            'product_name' => 'Test order-form course',
            'product_price' => 100,
            'publigo_sent' => 0,
            'orderer_name' => 'Jan Kowalski',
            'orderer_email' => 'orderer-'.uniqid().'@example.test',
            'buyer_name' => 'Jan Kowalski',
            'buyer_address' => 'Testowa 1',
            'buyer_postal_code' => '00-001',
            'buyer_city' => 'Warszawa',
            'invoice_payment_delay' => 14,
            'payment_mode' => FormOrder::PAYMENT_MODE_DEFERRED_INVOICE,
            'payment_status' => FormOrder::PAYMENT_STATUS_SUBMITTED,
            'submission_source' => FormOrder::SUBMISSION_SOURCE_PNEDU_ORDER_FORM,
            'status_completed' => 0,
        ]);

        $this->createdOrderIds[] = $order->id;

        FormOrderParticipant::syncFromFormOrder(
            $order,
            'Anna',
            'Nowak',
            $participantEmail
        );

        return $order->fresh('primaryParticipant');
    }

    private function courseId(): int
    {
        $courseId = DB::connection('pneadm')
            ->table('courses')
            ->orderBy('id')
            ->value('id');

        if ($courseId === null) {
            $this->markTestSkipped('Brak kursu w tabeli pneadm.courses.');
        }

        return (int) $courseId;
    }

    private function skipWhenPneadmTablesAreUnavailable(): void
    {
        if (! $this->requiredTablesAvailable()) {
            $this->markTestSkipped('Brak wymaganych tabel pneadm w środowisku testowym.');
        }
    }

    private function requiredTablesAvailable(): bool
    {
        try {
            return Schema::connection('pneadm')->hasTable('courses')
                && Schema::connection('pneadm')->hasTable('form_orders')
                && Schema::connection('pneadm')->hasTable('form_order_participants');
        } catch (\Throwable) {
            return false;
        }
    }
}
