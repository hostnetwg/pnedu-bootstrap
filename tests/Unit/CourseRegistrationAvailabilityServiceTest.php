<?php

namespace Tests\Unit;

use App\Models\Course;
use App\Services\CourseRegistrationAvailabilityService;
use Illuminate\Http\Request;
use Tests\TestCase;

class CourseRegistrationAvailabilityServiceTest extends TestCase
{
    public function test_returns_successor_for_closed_course(): void
    {
        $successor = new Course;
        $successor->forceFill([
            'id' => 20,
            'start_date' => '2026-10-10 10:00:00',
            'registration_closed_at' => null,
        ]);

        $course = new Course;
        $course->forceFill([
            'id' => 10,
            'registration_closed_at' => now(),
            'registration_successor_course_id' => 20,
        ]);
        $course->setRelation('registrationSuccessor', $successor);

        $service = new CourseRegistrationAvailabilityService;

        $this->assertTrue($service->isClosed($course));
        $this->assertSame($successor, $service->successor($course));
    }

    public function test_does_not_return_closed_successor(): void
    {
        $successor = new Course;
        $successor->forceFill([
            'id' => 20,
            'registration_closed_at' => now(),
        ]);

        $course = new Course;
        $course->forceFill([
            'id' => 10,
            'registration_closed_at' => now(),
            'registration_successor_course_id' => 20,
        ]);
        $course->setRelation('registrationSuccessor', $successor);

        $service = new CourseRegistrationAvailabilityService;

        $this->assertNull($service->successor($course));
    }

    public function test_closed_message_mentions_successor_date(): void
    {
        $successor = new Course;
        $successor->forceFill([
            'id' => 20,
            'start_date' => '2026-10-10 10:00:00',
        ]);

        $course = new Course;
        $course->forceFill([
            'id' => 10,
            'start_date' => '2026-09-02 10:00:00',
            'registration_closed_at' => now(),
            'registration_closed_message' => 'Ten termin jest już pełny.',
        ]);

        $service = new CourseRegistrationAvailabilityService;
        $message = $service->closedMessage($course, $successor);

        $this->assertStringContainsString('środa, 02.09.2026 10:00', $message);
        $this->assertStringContainsString('Ten termin jest już pełny.', $message);
        $this->assertStringContainsString('sobota, 10.10.2026 10:00', $message);
    }

    public function test_redirect_closed_registration_goes_to_successor_course_page(): void
    {
        $successor = new Course;
        $successor->forceFill([
            'id' => 20,
            'registration_closed_at' => null,
        ]);

        $course = new Course;
        $course->forceFill([
            'id' => 10,
            'registration_closed_at' => now(),
            'registration_successor_course_id' => 20,
        ]);
        $course->setRelation('registrationSuccessor', $successor);

        $request = Request::create('/courses/10/order-form?utm_source=newsletter', 'GET', [
            'utm_source' => 'newsletter',
        ]);

        $service = new CourseRegistrationAvailabilityService;
        $response = $service->redirectClosedRegistrationToSuccessor($course, $request);

        $this->assertNotNull($response);
        $this->assertTrue($response->isRedirect());
        $target = $response->getTargetUrl();
        $this->assertStringContainsString('/courses/20', $target);
        $this->assertStringNotContainsString('order-form', $target);
        $this->assertStringContainsString('registration_redirected_from=10', $target);
        $this->assertStringContainsString('utm_source=newsletter', $target);
    }
}
