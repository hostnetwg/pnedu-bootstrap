<?php

namespace Tests\Unit;

use App\Models\ProductPrice;
use App\Services\ProductLegalCheckoutService;
use App\Support\OrderFormCustomerProfile;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProductLegalCheckoutServiceTest extends TestCase
{
    private ProductLegalCheckoutService $service;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.timezone' => 'Europe/Warsaw']);
        $this->service = app(ProductLegalCheckoutService::class);
    }

    public function test_immediate_access_requires_waiver_only_for_consumers(): void
    {
        $price = new ProductPrice;

        $this->assertTrue($this->service->requiresEarlyPerformanceStatement(
            OrderFormCustomerProfile::PERSON,
            $price
        ));
        $this->assertTrue($this->service->requiresEarlyPerformanceStatement(
            OrderFormCustomerProfile::JDG,
            $price
        ));
        $this->assertFalse($this->service->requiresEarlyPerformanceStatement(
            OrderFormCustomerProfile::SCHOOL,
            $price
        ));
    }

    public function test_presale_inside_fourteen_days_requires_waiver(): void
    {
        $concluded = CarbonImmutable::parse('2026-09-13 12:00', 'Europe/Warsaw');
        $price = new ProductPrice;
        $price->forceFill([
            'access_starts_at' => $concluded->addDays(3),
        ]);

        $this->assertTrue($this->service->requiresEarlyPerformanceStatement(
            OrderFormCustomerProfile::PERSON,
            $price,
            $concluded
        ));
    }

    public function test_presale_after_fourteen_days_skips_waiver(): void
    {
        $concluded = CarbonImmutable::parse('2026-09-13 12:00', 'Europe/Warsaw');
        $price = new ProductPrice;
        $price->forceFill([
            'access_starts_at' => $concluded->addMonth(),
        ]);

        $this->assertFalse($this->service->requiresEarlyPerformanceStatement(
            OrderFormCustomerProfile::PERSON,
            $price,
            $concluded
        ));

        $this->service->assertRequiredStatementAccepted(
            OrderFormCustomerProfile::PERSON,
            false,
            $price,
            $concluded
        );

        $evidence = $this->service->evidence(OrderFormCustomerProfile::PERSON, false, $price, $concluded);
        $this->assertNull($evidence['early_performance_accepted_at']);
        $this->assertNull($evidence['early_performance_statement_text']);
    }

    public function test_immediate_consumer_checkout_rejects_missing_waiver(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->assertRequiredStatementAccepted(
            OrderFormCustomerProfile::PERSON,
            false,
            new ProductPrice
        );
    }

    public function test_unchecked_waiver_is_not_stored_as_consent(): void
    {
        $evidence = $this->service->evidence(OrderFormCustomerProfile::PERSON, false, new ProductPrice);

        $this->assertNull($evidence['early_performance_accepted_at']);
        $this->assertNull($evidence['early_performance_statement_text']);
        $this->assertNull($evidence['early_performance_kind']);
    }

    public function test_accepted_waiver_stores_exact_statement_text(): void
    {
        $evidence = $this->service->evidence(OrderFormCustomerProfile::JDG, true, new ProductPrice);

        $this->assertNotNull($evidence['early_performance_accepted_at']);
        $this->assertSame($this->service->statement(), $evidence['early_performance_statement_text']);
        $this->assertSame($this->service->statementVersion(), $evidence['early_performance_statement_version']);
    }

    public function test_unapproved_product_qualification_keeps_service_and_digital_course_wording(): void
    {
        config(['legal.product_qualification' => 'digital_content']);

        $this->assertSame('service_and_digital', $this->service->activeStatementKind());
        $this->assertStringContainsString('rozpoczęcie kursu', $this->service->statement());
        $this->assertStringNotContainsString('realizacji szkolenia', $this->service->statement());
        $this->assertSame('2026-09-13-course-v1', $this->service->statementVersion());
    }
}
