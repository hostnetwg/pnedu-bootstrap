<?php

namespace Tests\Unit;

use App\Services\OrderFormParticipantService;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class OrderFormEmailFilterValidationTest extends TestCase
{
    public function test_filter_rejects_domain_without_tld_and_accepts_normal_email(): void
    {
        $invalid = Validator::make(
            ['contact_email' => 'spnowagora@gminakrzeszowice'],
            ['contact_email' => 'required|email:filter|max:255']
        );
        $this->assertTrue($invalid->fails());
        $this->assertArrayHasKey('contact_email', $invalid->errors()->toArray());

        $valid = Validator::make(
            ['contact_email' => 'spnowagora@gminakrzeszowice.pl'],
            ['contact_email' => 'required|email:filter|max:255']
        );
        $this->assertFalse($valid->fails());
    }

    public function test_participant_service_rules_reject_email_without_tld(): void
    {
        $rules = app(OrderFormParticipantService::class)->validationRules('organisation');

        $validator = Validator::make([
            'participant_first_name' => 'Anna',
            'participant_last_name' => 'Kowalska',
            'participant_email' => 'spnowagora@gminakrzeszowice',
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('participant_email', $validator->errors()->toArray());
    }

    public function test_rfc_alone_would_accept_email_without_tld(): void
    {
        // Dokumentuje lukę, którą zamykamy przez email:filter.
        $rfc = Validator::make(
            ['email' => 'spnowagora@gminakrzeszowice'],
            ['email' => 'required|email']
        );
        $this->assertFalse($rfc->fails());
    }
}
