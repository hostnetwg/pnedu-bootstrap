<?php

namespace Tests\Unit;

use App\Services\FormOrderCheckoutResumeService;
use Tests\TestCase;

class FormOrderCheckoutResumeServiceTest extends TestCase
{
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
}
