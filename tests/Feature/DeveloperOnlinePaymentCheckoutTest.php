<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\PaymentDisplayOption;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DeveloperOnlinePaymentCheckoutTest extends TestCase
{
    private ?PaymentDisplayOption $displayOptions = null;

    private bool $originalEnabled = false;

    private bool $originalSandbox = true;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            if (! Schema::connection('pneadm')->hasColumn('payment_display_options', 'developer_online_payment_test_enabled')) {
                $this->markTestSkipped('Brak kolumn developer_online_payment_test_* w payment_display_options.');
            }
        } catch (\Throwable) {
            $this->markTestSkipped('Brak połączenia z testową bazą pneadm.');
        }

        $this->displayOptions = PaymentDisplayOption::query()->first();
        if (! $this->displayOptions) {
            $this->markTestSkipped('Brak rekordu payment_display_options.');
        }

        $this->originalEnabled = (bool) $this->displayOptions->developer_online_payment_test_enabled;
        $this->originalSandbox = (bool) ($this->displayOptions->developer_online_payment_sandbox_gateway ?? true);
    }

    protected function tearDown(): void
    {
        if ($this->displayOptions) {
            $this->displayOptions->forceFill([
                'developer_online_payment_test_enabled' => $this->originalEnabled,
                'developer_online_payment_sandbox_gateway' => $this->originalSandbox,
            ])->save();
        }

        parent::tearDown();
    }

    public function test_developer_sees_symbolic_payment_alert_on_order_form(): void
    {
        $course = $this->activePaidCourse();
        $this->enableDeveloperPaymentTest();

        $user = User::factory()->create([
            'email' => 'waldemar.grabowski@hostnet.pl',
        ]);

        $this->actingAs($user)
            ->get(route('payment.order-form', $course->id))
            ->assertOk()
            ->assertSee('Płatność testowa deweloperska', false)
            ->assertSee('5,00 PLN', false);
    }

    public function test_other_logged_in_user_does_not_see_symbolic_payment_alert(): void
    {
        $course = $this->activePaidCourse();
        $this->enableDeveloperPaymentTest();

        $user = User::factory()->create([
            'email' => 'customer@example.com',
        ]);

        $this->actingAs($user)
            ->get(route('payment.order-form', $course->id))
            ->assertOk()
            ->assertDontSee('Płatność testowa deweloperska', false);
    }

    private function enableDeveloperPaymentTest(): void
    {
        $this->displayOptions->forceFill([
            'show_order_form' => true,
            'show_order_form_v2' => true,
            'developer_online_payment_test_enabled' => true,
        ])->save();
    }

    private function activePaidCourse(): Course
    {
        $course = Course::query()
            ->where('is_active', true)
            ->where('is_paid', true)
            ->orderByDesc('id')
            ->first();

        if (! $course) {
            $this->markTestSkipped('Brak aktywnego płatnego kursu w bazie testowej.');
        }

        return $course;
    }
}
