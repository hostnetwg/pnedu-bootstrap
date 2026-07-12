<?php

namespace Tests\Unit;

use App\Models\PaymentDisplayOption;
use App\Models\User;
use App\Support\DeveloperOnlinePaymentTest;
use Tests\TestCase;

class DeveloperOnlinePaymentTestTest extends TestCase
{
    public function test_symbolic_amount_requires_enabled_setting_and_developer_email(): void
    {
        $options = [
            'developer_online_payment_test_enabled' => true,
        ];

        $this->assertFalse(DeveloperOnlinePaymentTest::shouldApplySymbolicAmount($options, null));

        $other = new User(['email' => 'other@example.com']);
        $this->assertFalse(DeveloperOnlinePaymentTest::shouldApplySymbolicAmount($options, $other));

        $developer = new User(['email' => 'waldemar.grabowski@hostnet.pl']);
        $this->assertTrue(DeveloperOnlinePaymentTest::shouldApplySymbolicAmount($options, $developer));

        $options['developer_online_payment_test_enabled'] = false;
        $this->assertFalse(DeveloperOnlinePaymentTest::shouldApplySymbolicAmount($options, $developer));
    }

    public function test_resolve_checkout_amount_returns_five_pln_for_developer(): void
    {
        $options = ['developer_online_payment_test_enabled' => true];
        $developer = new User(['email' => 'luman0599@gmail.com']);

        $this->assertSame(5.0, DeveloperOnlinePaymentTest::resolveCheckoutAmount(199.0, $options, $developer));
    }

    public function test_unrestricted_test_mode_does_not_apply_symbolic_payment(): void
    {
        $options = [
            'order_form_auto_fill_test_data' => true,
            'developer_online_payment_test_enabled' => false,
        ];

        $this->assertFalse(DeveloperOnlinePaymentTest::shouldApplySymbolicAmount($options, null));
        $this->assertSame(120.0, DeveloperOnlinePaymentTest::resolveCheckoutAmount(120.0, $options, null));
    }

    public function test_sandbox_gateway_override_only_for_developer_with_setting(): void
    {
        $options = [
            'developer_online_payment_test_enabled' => true,
            'developer_online_payment_sandbox_gateway' => false,
        ];
        $developer = new User(['email' => 'waldemar.grabowski@hostnet.pl']);

        $this->assertNull(DeveloperOnlinePaymentTest::sandboxGatewayOverride($options, null));
        $this->assertFalse(DeveloperOnlinePaymentTest::sandboxGatewayOverride($options, $developer));

        $options['developer_online_payment_sandbox_gateway'] = true;
        $this->assertTrue(DeveloperOnlinePaymentTest::sandboxGatewayOverride($options, $developer));
    }

    public function test_developer_emails_match_payment_display_option_whitelist(): void
    {
        foreach (PaymentDisplayOption::ORDER_FORM_AUTO_FILL_DEVELOPER_EMAILS as $email) {
            $this->assertTrue(DeveloperOnlinePaymentTest::isDeveloperAccount(new User(['email' => $email])));
        }
    }
}
