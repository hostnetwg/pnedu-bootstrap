<?php

namespace Tests\Unit;

use App\Services\OrderFormParticipantService;
use Illuminate\Http\Request;
use Tests\TestCase;

class OrderFormParticipantServiceTest extends TestCase
{
    public function test_person_allows_only_one_participant(): void
    {
        $service = new OrderFormParticipantService;
        $this->assertFalse($service->allowsMultiple('person'));
        $this->assertTrue($service->allowsMultiple('organisation'));
    }

    public function test_total_price_multiplies_unit_by_count(): void
    {
        $service = new OrderFormParticipantService;
        $this->assertSame(597.0, $service->totalPrice(199.0, 3));
        $this->assertSame(199.0, $service->totalPrice(199.0, 1));
        $this->assertNull($service->totalPrice(null, 2));
    }

    public function test_parse_from_request_slices_person_to_one(): void
    {
        $service = new OrderFormParticipantService;
        $request = Request::create('/test', 'POST', [
            'participants' => [
                ['first_name' => 'A', 'last_name' => 'B', 'email' => 'a@example.test'],
                ['first_name' => 'C', 'last_name' => 'D', 'email' => 'c@example.test'],
            ],
        ]);

        $rows = $service->parseFromRequest($request, 'person');
        $this->assertCount(1, $rows);
        $this->assertSame('a@example.test', $rows[0]['email']);
    }

    public function test_parse_from_request_keeps_multiple_for_organisation(): void
    {
        $service = new OrderFormParticipantService;
        $request = Request::create('/test', 'POST', [
            'participants' => [
                ['first_name' => 'A', 'last_name' => 'B', 'email' => 'a@example.test'],
                ['first_name' => 'C', 'last_name' => 'D', 'email' => 'c@example.test'],
            ],
        ]);

        $rows = $service->parseFromRequest($request, 'organisation');
        $this->assertCount(2, $rows);
    }

    public function test_max_count_defaults_to_fifty(): void
    {
        $service = new OrderFormParticipantService;
        $this->assertSame(50, $service->maxCount());
    }
}
