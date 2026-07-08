<?php

namespace Tests\Unit;

use App\Models\FormOrder;
use App\Services\OrderFormRecipientIdentityService;
use Illuminate\Http\Request;
use Tests\TestCase;

class OrderFormRecipientIdentityServiceTest extends TestCase
{
    private OrderFormRecipientIdentityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OrderFormRecipientIdentityService;
    }

    public function test_resolves_nip_only_without_ksef_metadata(): void
    {
        $request = Request::create('/', 'POST', [
            'recipient_name' => 'Szkoła Podstawowa',
            'recipient_nip' => '123-456-32-18',
        ]);

        $payload = $this->service->resolveStoragePayload($request, '7392137630');

        $this->assertSame('1234563218', $payload['recipient_nip']);
        $this->assertSame(OrderFormRecipientIdentityService::KSEF_SOURCE_NONE, $payload['ksef_entity_source']);
        $this->assertNull($payload['ksef_additional_entity_id_type']);
    }

    public function test_resolves_nip_and_idwew_together(): void
    {
        $request = Request::create('/', 'POST', [
            'recipient_name' => 'Oddział 12',
            'recipient_nip' => '9876543210',
            'recipient_internal_id' => '00001',
        ]);

        $payload = $this->service->resolveStoragePayload($request, '7392137630');

        $this->assertSame('9876543210', $payload['recipient_nip']);
        $this->assertSame(OrderFormRecipientIdentityService::KSEF_SOURCE_RECIPIENT, $payload['ksef_entity_source']);
        $this->assertSame('IDWew', $payload['ksef_additional_entity_id_type']);
        $this->assertSame('7392137630-00001', $payload['ksef_additional_entity_identifier']);
    }

    public function test_validates_recipient_nip_when_recipient_data_present(): void
    {
        $request = Request::create('/', 'POST', [
            'recipient_name' => 'Oddział',
        ]);

        $error = $this->service->validateRecipientIdentity($request, '7392137630');

        $this->assertNotNull($error);
        $this->assertSame('recipient_nip', $error['field']);
    }

    public function test_validates_idwew_requires_buyer_nip_when_internal_id_filled(): void
    {
        $request = Request::create('/', 'POST', [
            'recipient_name' => 'Oddział',
            'recipient_nip' => '9876543210',
            'recipient_internal_id' => '00001',
        ]);

        $error = $this->service->validateRecipientIdentity($request, null);

        $this->assertNotNull($error);
        $this->assertSame('recipient_internal_id', $error['field']);
    }

    public function test_prefill_from_form_order_with_idwew_and_nip(): void
    {
        $order = new FormOrder;
        $order->forceFill([
            'recipient_nip' => '9876543210',
            'buyer_nip' => '7392137630',
            'ksef_additional_entity_id_type' => 'IDWew',
            'ksef_additional_entity_identifier' => '7392137630-00123',
        ]);

        $prefill = $this->service->prefillFromFormOrder($order);

        $this->assertSame('9876543210', $prefill['recipient_nip']);
        $this->assertSame('00123', $prefill['recipient_internal_id']);
    }

    public function test_resolves_idwew_from_full_hyphenated_value(): void
    {
        $request = Request::create('/', 'POST', [
            'recipient_name' => 'Oddział 12',
            'recipient_nip' => '9876543210',
            'recipient_internal_id' => '7392137630-00001',
        ]);

        $payload = $this->service->resolveStoragePayload($request, '7392137630');

        $this->assertSame('9876543210', $payload['recipient_nip']);
        $this->assertSame('7392137630-00001', $payload['ksef_additional_entity_identifier']);
    }

    public function test_rejects_non_numeric_suffix(): void
    {
        $request = Request::create('/', 'POST', [
            'recipient_name' => 'Oddział',
            'recipient_nip' => '9876543210',
            'recipient_internal_id' => 'A1B2C',
        ]);

        $error = $this->service->validateRecipientIdentity($request, '7392137630');

        $this->assertNotNull($error);
        $this->assertSame('recipient_internal_id', $error['field']);
    }
}
