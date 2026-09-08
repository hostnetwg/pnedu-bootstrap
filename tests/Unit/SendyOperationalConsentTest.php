<?php

namespace Tests\Unit;

use App\Models\Course;
use App\Models\OnlinePaymentOrder;
use App\Services\SendyService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendyOperationalConsentTest extends TestCase
{
    public function test_course_operational_subscription_does_not_claim_marketing_consent(): void
    {
        Http::fake([
            '*/api/subscribers/active-subscriber-count.php' => Http::response('1'),
            '*/subscribe' => Http::response('true'),
        ]);

        $course = new Course;
        $course->forceFill([
            'id' => 123,
            'sendy_suppression_list_id' => 'course-list',
            'start_date' => CarbonImmutable::parse('2026-09-20 10:00', 'Europe/Warsaw'),
        ]);

        $sendy = new SendyService('https://sendy.example', 'secret');
        $sendy->subscribeOrderFormContacts($course, [
            'contact_email' => 'buyer@example.com',
            'contact_name' => 'Buyer',
            'participant_email' => 'participant@example.com',
            'participant_first_name' => 'Jan',
            'participant_last_name' => 'Nowak',
        ]);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/subscribe')
            && ! array_key_exists('gdpr', $request->data()));
    }

    public function test_marketing_subscription_records_explicit_consent_flag(): void
    {
        Http::fake(['*/subscribe' => Http::response('true')]);

        $sendy = new SendyService('https://sendy.example', 'secret');
        $this->assertTrue($sendy->subscribeHomepageNewsletter('reader@example.com'));

        Http::assertSent(fn (Request $request): bool => $request['gdpr'] === 'true');
    }

    public function test_paid_standalone_order_uses_operational_subscription_without_marketing_flag(): void
    {
        Http::fake([
            '*/api/subscribers/active-subscriber-count.php' => Http::response('1'),
            '*/subscribe' => Http::response('true'),
        ]);

        $course = new Course;
        $course->forceFill([
            'sendy_suppression_list_id' => 'course-list',
            'start_date' => CarbonImmutable::parse('2026-09-20 10:00', 'Europe/Warsaw'),
        ]);
        $order = new OnlinePaymentOrder;
        $order->forceFill([
            'email' => 'participant@example.com',
            'first_name' => 'Jan',
            'last_name' => 'Nowak',
        ]);

        (new SendyService('https://sendy.example', 'secret'))
            ->subscribeStandaloneOnlineOperational($course, $order);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/subscribe')
            && ! array_key_exists('gdpr', $request->data()));
    }
}
