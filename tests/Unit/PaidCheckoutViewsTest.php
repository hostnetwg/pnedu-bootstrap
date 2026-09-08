<?php

namespace Tests\Unit;

use Tests\TestCase;

class PaidCheckoutViewsTest extends TestCase
{
    public function test_all_paid_checkout_views_use_shared_legal_block_and_payment_button_wording(): void
    {
        foreach ([
            'courses/order-form-v2.blade.php',
            'courses/order-form.blade.php',
            'courses/deferred-order.blade.php',
            'courses/pay-online.blade.php',
        ] as $relativePath) {
            $source = file_get_contents(resource_path('views/'.$relativePath));

            $this->assertIsString($source, $relativePath);
            $this->assertStringContainsString(
                'courses.partials.paid-checkout-legal',
                $source,
                $relativePath
            );
            $this->assertStringContainsString(
                'Zamówienie z obowiązkiem zapłaty',
                $source,
                $relativePath
            );
        }

        $v2 = file_get_contents(resource_path('views/courses/order-form-v2.blade.php'));
        $this->assertIsString($v2);
        $this->assertStringContainsString('recipient_id_type', $v2);
        $this->assertStringContainsString('Identyfikator wewnętrzny', $v2);
        $this->assertStringContainsString('gusButton.hidden = !useNip', $v2);

        $guard = file_get_contents(resource_path('views/courses/partials/order-form-submit-guard.blade.php'));
        $this->assertIsString($guard);
        $this->assertStringContainsString('checkValidity', $guard);
        $this->assertStringContainsString('resetSubmitButtons', $guard);
    }
}
