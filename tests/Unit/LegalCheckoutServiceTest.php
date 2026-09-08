<?php

namespace Tests\Unit;

use App\Models\Course;
use App\Services\LegalCheckoutService;
use App\Support\OrderFormCustomerProfile;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LegalCheckoutServiceTest extends TestCase
{
    private LegalCheckoutService $service;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.timezone' => 'Europe/Warsaw']);
        $this->service = app(LegalCheckoutService::class);
    }

    public function test_statement_is_required_only_for_protected_profiles_within_fourteen_days(): void
    {
        $orderedAt = CarbonImmutable::parse('2026-09-01 10:00', 'Europe/Warsaw');
        $course = $this->paidCourse('2026-09-15 23:59:59');

        $this->assertTrue($this->service->requiresEarlyPerformanceStatement(
            $course,
            OrderFormCustomerProfile::PERSON,
            $orderedAt
        ));
        $this->assertTrue($this->service->requiresEarlyPerformanceStatement(
            $course,
            OrderFormCustomerProfile::JDG,
            $orderedAt
        ));
        $this->assertFalse($this->service->requiresEarlyPerformanceStatement(
            $course,
            OrderFormCustomerProfile::SCHOOL,
            $orderedAt
        ));
        $this->assertFalse($this->service->requiresEarlyPerformanceStatement(
            $course,
            OrderFormCustomerProfile::ORGANISATION,
            $orderedAt
        ));
    }

    public function test_course_after_end_of_fourteenth_day_does_not_require_statement(): void
    {
        $orderedAt = CarbonImmutable::parse('2026-09-01 23:50', 'Europe/Warsaw');
        $course = $this->paidCourse('2026-09-16 00:00:00');

        $this->assertFalse($this->service->requiresEarlyPerformanceStatement(
            $course,
            OrderFormCustomerProfile::PERSON,
            $orderedAt
        ));
    }

    public function test_backend_rejects_missing_required_statement(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->assertRequiredStatementAccepted(
            $this->paidCourse('2026-09-05 10:00:00'),
            OrderFormCustomerProfile::PERSON,
            false,
            CarbonImmutable::parse('2026-09-01 10:00', 'Europe/Warsaw')
        );
    }

    public function test_evidence_contains_immutable_terms_hash_and_statement_metadata(): void
    {
        $evidence = $this->service->evidence(
            $this->paidCourse('2026-09-05 10:00:00'),
            OrderFormCustomerProfile::JDG,
            true,
            CarbonImmutable::parse('2026-09-01 10:00', 'Europe/Warsaw')
        );

        $this->assertSame('jdg', $evidence['customer_profile']);
        $this->assertSame(config('legal.terms.current_version'), $evidence['terms_version']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $evidence['terms_hash']);
        $this->assertSame('service_and_digital', $evidence['early_performance_scope']);
        $this->assertSame('2026-09-08-v2', $evidence['early_performance_statement_version']);
        $this->assertStringContainsString('Proszę o rozpoczęcie realizacji szkolenia', $this->service->statement());
        $this->assertNotNull($evidence['early_performance_accepted_at']);
    }

    private function paidCourse(string $start): Course
    {
        $course = new Course;
        $course->forceFill([
            'is_paid' => true,
            'start_date' => CarbonImmutable::parse($start, 'Europe/Warsaw'),
        ]);

        return $course;
    }
}
