<?php

namespace Tests\Unit;

use App\Support\OrderFormTestData;
use Tests\TestCase;

class OrderFormTestDataTest extends TestCase
{
    public function test_defaults_include_fields_used_by_v2_fill_button(): void
    {
        $data = OrderFormTestData::defaults();

        $this->assertSame('school', $data['customer_profile']);
        $this->assertSame('organisation', $data['buyer_type']);
        $this->assertSame('deferred', $data['payment_type']);
        $this->assertNotEmpty($data['contact_name']);
        $this->assertNotEmpty($data['buyer_name']);
        $this->assertNotEmpty($data['participant_email']);
    }
}
