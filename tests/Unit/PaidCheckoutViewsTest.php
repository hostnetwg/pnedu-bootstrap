<?php

namespace Tests\Unit;

use Tests\TestCase;

class PaidCheckoutViewsTest extends TestCase
{
    /**
     * @return list<string>
     */
    private function activeCheckoutViews(): array
    {
        return [
            'courses/order-form-v2.blade.php',
            'courses/order-form.blade.php',
            'courses/deferred-order.blade.php',
            'courses/pay-online.blade.php',
        ];
    }

    public function test_all_paid_checkout_views_use_shared_legal_block_and_confirm_purchase_button(): void
    {
        foreach ($this->activeCheckoutViews() as $relativePath) {
            $source = file_get_contents(resource_path('views/'.$relativePath));

            $this->assertIsString($source, $relativePath);
            $this->assertStringContainsString(
                'courses.partials.paid-checkout-legal',
                $source,
                $relativePath
            );
            $this->assertStringContainsString(
                'Potwierdzam zakup',
                $source,
                $relativePath
            );
            $this->assertStringNotContainsString(
                'Zamówienie z obowiązkiem zapłaty',
                $source,
                $relativePath
            );
            $this->assertStringNotContainsString('Kupuję i płacę', $source, $relativePath);
            $this->assertStringNotContainsString('Zapłać teraz', $source, $relativePath);
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
        $this->assertStringContainsString('spinner-border', $guard);
        $this->assertStringContainsString('showSubmittingState', $guard);
        $this->assertStringContainsString('Potwierdzam zakup', $guard);
        $this->assertStringNotContainsString('Zamówienie z obowiązkiem zapłaty', $guard);
    }

    public function test_deferred_summary_shows_payment_term_and_hides_online_redirect_hint(): void
    {
        $deferred = file_get_contents(resource_path('views/courses/deferred-order.blade.php'));
        $partial = file_get_contents(resource_path('views/courses/partials/paid-checkout-legal.blade.php'));

        $this->assertIsString($deferred);
        $this->assertIsString($partial);
        $this->assertStringContainsString("'paymentMode' => 'deferred'", $deferred);
        $this->assertStringContainsString('faktura z odroczonym terminem', $deferred);
        $this->assertStringContainsString('Potwierdzam zakup', $deferred);
        $this->assertStringContainsString('data-online-payment-hint', $partial);
        $this->assertStringContainsString('onlineHint.hidden = !isOnline', $partial);
        $this->assertStringContainsString(
            'Po potwierdzeniu zakupu przejdziesz do bezpiecznej płatności online.',
            $partial
        );
    }

    public function test_online_forms_show_gateway_label_and_redirect_hint_only_for_online_payment(): void
    {
        $partial = file_get_contents(resource_path('views/courses/partials/paid-checkout-legal.blade.php'));
        $payOnline = file_get_contents(resource_path('views/courses/pay-online.blade.php'));
        $v2 = file_get_contents(resource_path('views/courses/order-form-v2.blade.php'));

        $this->assertIsString($partial);
        $this->assertIsString($payOnline);
        $this->assertIsString($v2);
        $this->assertStringContainsString("'paymentMode' => 'online'", $payOnline);
        $this->assertStringContainsString('płatność online', $partial);
        $this->assertStringContainsString("gateway.value.toUpperCase()", $partial);
        $this->assertStringContainsString('PayU', $v2);
        $this->assertStringContainsString('Paynow', $v2);
        $this->assertStringContainsString('PayU', $payOnline);
        $this->assertStringContainsString('Paynow', $payOnline);
        $this->assertStringContainsString('isOnline', $partial);
        $this->assertStringContainsString('isDeferred', $partial);
    }
}
