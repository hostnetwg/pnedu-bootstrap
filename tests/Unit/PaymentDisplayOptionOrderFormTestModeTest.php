<?php

namespace Tests\Unit;

use App\Models\PaymentDisplayOption;
use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PaymentDisplayOptionOrderFormTestModeTest extends TestCase
{
    public function test_unrestricted_setting_enables_test_mode_without_login(): void
    {
        $options = [
            'order_form_auto_fill_test_data' => true,
            'order_form_auto_fill_test_data_developers_only' => false,
        ];

        $this->assertTrue(PaymentDisplayOption::isOrderFormTestModeEnabled($options, null));
    }

    public function test_developers_only_requires_allowed_logged_in_email(): void
    {
        $options = [
            'order_form_auto_fill_test_data' => false,
            'order_form_auto_fill_test_data_developers_only' => true,
        ];

        $this->assertFalse(PaymentDisplayOption::isOrderFormTestModeEnabled($options, null));

        $otherUser = new User(['email' => 'other@example.com']);
        $this->assertFalse(PaymentDisplayOption::isOrderFormTestModeEnabled($options, $otherUser));

        $developer = new User(['email' => 'waldemar.grabowski@hostnet.pl']);
        $this->assertTrue(PaymentDisplayOption::isOrderFormTestModeEnabled($options, $developer));

        $developerCaseInsensitive = new User(['email' => 'Luman0599@Gmail.com']);
        $this->assertTrue(PaymentDisplayOption::isOrderFormTestModeEnabled($options, $developerCaseInsensitive));
    }

    public function test_both_disabled(): void
    {
        $options = [
            'order_form_auto_fill_test_data' => false,
            'order_form_auto_fill_test_data_developers_only' => false,
        ];

        $developer = new User(['email' => 'waldemar.grabowski@hostnet.pl']);
        $this->assertFalse(PaymentDisplayOption::isOrderFormTestModeEnabled($options, $developer));
    }

    public function test_unrestricted_auto_fill_expires_on_production_after_ttl(): void
    {
        $this->app['env'] = 'production';
        Carbon::setTestNow('2026-06-17 12:00:00');

        $fresh = Carbon::parse('2026-06-17 11:59:30');
        $this->assertFalse(PaymentDisplayOption::isUnrestrictedAutoFillExpired($fresh));

        $expired = Carbon::parse('2026-06-17 11:58:59');
        $this->assertTrue(PaymentDisplayOption::isUnrestrictedAutoFillExpired($expired));
        $this->assertTrue(PaymentDisplayOption::isUnrestrictedAutoFillExpired(null));

        Carbon::setTestNow();
    }

    public function test_unrestricted_auto_fill_does_not_expire_outside_production(): void
    {
        $this->app['env'] = 'local';
        $this->assertFalse(PaymentDisplayOption::unrestrictedAutoFillShouldExpire());
    }

    public function test_developers_only_is_not_affected_by_unrestricted_expiry_helpers(): void
    {
        $this->app['env'] = 'production';
        Carbon::setTestNow('2026-06-17 12:00:00');

        $options = [
            'order_form_auto_fill_test_data' => false,
            'order_form_auto_fill_test_data_developers_only' => true,
        ];

        $developer = new User(['email' => 'luman0599@gmail.com']);
        $this->assertTrue(PaymentDisplayOption::isOrderFormTestModeEnabled($options, $developer));

        Carbon::setTestNow();
    }

    public function test_test_mode_never_auto_fills_form_fields_on_load(): void
    {
        $unrestricted = [
            'order_form_auto_fill_test_data' => true,
            'order_form_auto_fill_test_data_developers_only' => false,
        ];
        $developersOnly = [
            'order_form_auto_fill_test_data' => false,
            'order_form_auto_fill_test_data_developers_only' => true,
        ];

        $this->assertTrue(PaymentDisplayOption::isOrderFormTestModeEnabled($unrestricted, null));
        $this->assertTrue(PaymentDisplayOption::isOrderFormTestModeEnabled(
            $developersOnly,
            new User(['email' => 'waldemar.grabowski@hostnet.pl'])
        ));
    }
}
