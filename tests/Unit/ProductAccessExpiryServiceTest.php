<?php

namespace Tests\Unit;

use App\Models\OrderItem;
use App\Models\ProductPrice;
use App\Services\ProductAccessExpiryService;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class ProductAccessExpiryServiceTest extends TestCase
{
    private ProductAccessExpiryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProductAccessExpiryService;
    }

    public function test_duration_starts_at_grant_for_new_or_expired_access(): void
    {
        $item = $this->durationItem(1, 'years');
        $grant = CarbonImmutable::parse('2026-09-12 10:00:00', 'UTC');

        $newAccess = $this->service->resolve($item, false, null, $grant);
        $expiredAccess = $this->service->resolve(
            $item,
            true,
            CarbonImmutable::parse('2026-09-01 10:00:00', 'UTC'),
            $grant
        );

        $this->assertSame('2027-09-12 10:00:00', $newAccess?->format('Y-m-d H:i:s'));
        $this->assertSame('2027-09-12 10:00:00', $expiredAccess?->format('Y-m-d H:i:s'));
    }

    public function test_duration_extends_active_access_from_current_expiry(): void
    {
        $item = $this->durationItem(1, 'years');
        $grant = CarbonImmutable::parse('2026-09-12 10:00:00', 'UTC');
        $currentExpiry = CarbonImmutable::parse('2027-02-01 12:00:00', 'UTC');

        $resolved = $this->service->resolve($item, true, $currentExpiry, $grant);

        $this->assertSame('2028-02-01 12:00:00', $resolved?->format('Y-m-d H:i:s'));
    }

    public function test_existing_unlimited_access_is_never_shortened(): void
    {
        $item = $this->durationItem(12, 'months');

        $resolved = $this->service->resolve(
            $item,
            true,
            null,
            CarbonImmutable::parse('2026-09-12 10:00:00', 'UTC')
        );

        $this->assertNull($resolved);
    }

    public function test_duration_starts_from_later_of_grant_and_scheduled_start(): void
    {
        $item = $this->durationItem(1, 'years');
        $item->access_starts_at = CarbonImmutable::parse('2026-12-01 10:00:00', 'UTC');
        $grant = CarbonImmutable::parse('2026-09-12 10:00:00', 'UTC');

        $resolved = $this->service->resolve($item, false, null, $grant);
        $expected = CarbonImmutable::instance($item->access_starts_at)->utc()->addYearsNoOverflow(1);

        $this->assertTrue($expected->gt($grant));
        $this->assertSame($expected->format('Y-m-d H:i:s'), $resolved?->format('Y-m-d H:i:s'));
    }

    public function test_unlimited_purchase_removes_expiry(): void
    {
        $item = new OrderItem(['access_policy' => ProductPrice::ACCESS_UNLIMITED]);

        $resolved = $this->service->resolve(
            $item,
            true,
            CarbonImmutable::parse('2027-01-01 00:00:00', 'UTC'),
            CarbonImmutable::parse('2026-09-12 10:00:00', 'UTC')
        );

        $this->assertNull($resolved);
    }

    public function test_fixed_date_does_not_shorten_later_existing_expiry(): void
    {
        $item = new OrderItem([
            'access_policy' => ProductPrice::ACCESS_FIXED_UNTIL,
            'access_expires_at' => '2027-01-01 00:00:00',
        ]);
        $laterExpiry = CarbonImmutable::parse('2027-06-01 00:00:00', 'UTC');

        $resolved = $this->service->resolve(
            $item,
            true,
            $laterExpiry,
            CarbonImmutable::parse('2026-09-12 10:00:00', 'UTC')
        );

        $this->assertSame($laterExpiry->toIso8601String(), $resolved?->toIso8601String());
    }

    private function durationItem(int $value, string $unit): OrderItem
    {
        return new OrderItem([
            'access_policy' => ProductPrice::ACCESS_DURATION_FROM_GRANT,
            'access_duration_value' => $value,
            'access_duration_unit' => $unit,
        ]);
    }
}
