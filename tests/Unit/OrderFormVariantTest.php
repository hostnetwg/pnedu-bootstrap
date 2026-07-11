<?php

namespace Tests\Unit;

use App\Support\OrderFormVariant;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OrderFormVariantTest extends TestCase
{
    #[DataProvider('resolveAvailableProvider')]
    public function test_resolve_available(string $preferred, array $visibility, string $expected): void
    {
        $this->assertSame($expected, OrderFormVariant::resolveAvailable($preferred, $visibility));
    }

    public static function resolveAvailableProvider(): array
    {
        return [
            'preferred v2 when enabled' => [OrderFormVariant::V2, ['show_order_form' => true, 'show_order_form_v2' => true], OrderFormVariant::V2],
            'preferred v2 when disabled falls back to legacy' => [OrderFormVariant::V2, ['show_order_form' => true, 'show_order_form_v2' => false], OrderFormVariant::LEGACY],
            'preferred legacy when only v2 enabled' => [OrderFormVariant::LEGACY, ['show_order_form' => false, 'show_order_form_v2' => true], OrderFormVariant::V2],
        ];
    }

    public function test_path_segment_and_route_name(): void
    {
        $this->assertSame('order-form', OrderFormVariant::pathSegment(OrderFormVariant::V2));
        $this->assertSame('order-form', OrderFormVariant::pathSegment(OrderFormVariant::LEGACY));
        $this->assertSame('payment.order-form', OrderFormVariant::publicRouteName());
        $this->assertSame('payment.order-form-v2', OrderFormVariant::routeName(OrderFormVariant::V2));
        $this->assertSame('payment.order-form', OrderFormVariant::routeName(OrderFormVariant::LEGACY));
        $this->assertSame(['form_variant' => 'v2'], OrderFormVariant::gatewayQuery(OrderFormVariant::V2));
        $this->assertSame([], OrderFormVariant::gatewayQuery(OrderFormVariant::GLOBAL));
        $this->assertTrue(OrderFormVariant::usesGlobalGateway(OrderFormVariant::GLOBAL));
        $this->assertSame(
            OrderFormVariant::GLOBAL,
            OrderFormVariant::storedCampaignVariant('global')
        );
        $this->assertSame(
            OrderFormVariant::LEGACY,
            OrderFormVariant::normalizeCampaignVariant('invalid')
        );
    }
}
