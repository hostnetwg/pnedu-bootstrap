<?php

namespace Tests\Unit;

use App\Support\OrderFormGateway;
use App\Support\OrderFormVariant;
use Illuminate\Http\Request;
use Tests\TestCase;

class OrderFormGatewayTest extends TestCase
{
    public function test_resolves_default_variant_from_display_options(): void
    {
        $gateway = new OrderFormGateway;
        $request = Request::create('/courses/1/order-form', 'GET');

        $variant = $gateway->resolveVariant($request, [
            'default_signup_order_form_variant' => OrderFormVariant::V2,
            'show_order_form' => true,
            'show_order_form_v2' => true,
        ]);

        $this->assertSame(OrderFormVariant::V2, $variant);
    }

    public function test_query_param_overrides_default_variant(): void
    {
        $gateway = new OrderFormGateway;
        $request = Request::create('/courses/1/order-form?form_variant=legacy', 'GET');

        $variant = $gateway->resolveVariant($request, [
            'default_signup_order_form_variant' => OrderFormVariant::V2,
            'show_order_form' => true,
            'show_order_form_v2' => true,
        ]);

        $this->assertSame(OrderFormVariant::LEGACY, $variant);
    }

    public function test_marks_resolved_variant_on_request_attributes(): void
    {
        $gateway = new OrderFormGateway;
        $request = Request::create('/courses/1/order-form', 'GET');

        $gateway->markResolvedVariant($request, OrderFormVariant::V2);

        $this->assertSame(
            OrderFormVariant::V2,
            OrderFormGateway::resolvedVariantFromRequest($request)
        );
    }
}
