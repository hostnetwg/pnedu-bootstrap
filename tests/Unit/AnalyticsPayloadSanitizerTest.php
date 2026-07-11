<?php

namespace Tests\Unit;

use App\Services\Analytics\AnalyticsPayloadSanitizer;
use Tests\TestCase;

class AnalyticsPayloadSanitizerTest extends TestCase
{
    public function test_it_keeps_only_allowed_metadata_and_removes_personal_data_recursively(): void
    {
        $sanitized = (new AnalyticsPayloadSanitizer)->sanitize([
            'event_name' => 'order_form_viewed',
            'event_category' => 'order_form',
            'email' => 'secret@example.com',
            'url' => 'https://pnedu.pl/courses/123?email=secret@example.com&utm_source=newsletter',
            'referrer' => 'https://facebook.com/post/1?fbclid=abc',
            'metadata_json' => [
                'buyer_type' => 'school',
                'participant_count' => 2,
                'nested' => [
                    'phone' => '123456789',
                    'payment_type' => 'deferred',
                ],
                'invoice_data' => 'forbidden',
                'query_parameters' => [
                    'utm_source' => 'newsletter',
                    'email' => 'secret@example.com',
                ],
            ],
        ]);

        $this->assertSame('order_form_viewed', $sanitized['event_name']);
        $this->assertSame('/courses/123', $sanitized['path']);
        $this->assertSame('facebook.com', $sanitized['referrer_domain']);
        $this->assertSame('school', $sanitized['metadata']['buyer_type']);
        $this->assertSame(2, $sanitized['metadata']['participant_count']);
        $this->assertSame(['utm_source' => 'newsletter'], $sanitized['metadata']['query_parameters']);
        $this->assertArrayNotHasKey('email', $sanitized);
        $this->assertArrayNotHasKey('url', $sanitized);
        $this->assertArrayNotHasKey('referrer', $sanitized);
        $this->assertArrayNotHasKey('invoice_data', $sanitized['metadata']);
        $this->assertArrayNotHasKey('nested', $sanitized['metadata']);
    }

    public function test_it_detects_forbidden_keys_in_nested_payloads(): void
    {
        $this->assertTrue((new AnalyticsPayloadSanitizer)->containsForbiddenKeys([
            'metadata' => [
                'safe' => true,
                'buyer_nip' => '123',
            ],
        ]));
    }

    public function test_it_keeps_schema_v2_technical_form_metadata_and_strips_pii(): void
    {
        $sanitized = (new AnalyticsPayloadSanitizer)->sanitize([
            'event_name' => 'client_validation_failed',
            'event_category' => 'validation',
            'tracking_schema_version' => 2,
            'metadata' => [
                'form_session_id' => '5d3e7b2e-34b2-4b2f-a6d4-2e78d2932457',
                'form_variant' => 'v2',
                'tracking_schema_version' => 2,
                'section_key' => 'contact',
                'field_key' => 'contact_email',
                'field_type' => 'email',
                'source' => 'manual',
                'has_value' => true,
                'seconds_from_page_load' => 12,
                'errors_count' => 2,
                'error_sections' => ['contact', 'payment'],
                'error_fields' => ['contact_email', 'payment_type'],
                'validation_error_codes' => ['required', 'email'],
                'field_value' => 'secret@example.com',
                'email' => 'secret@example.com',
                'nip' => '1234567890',
            ],
        ]);

        $metadata = $sanitized['metadata'];

        $this->assertSame(2, $sanitized['tracking_schema_version']);
        $this->assertSame('v2', $metadata['form_variant']);
        $this->assertSame('contact', $metadata['section_key']);
        $this->assertSame('contact_email', $metadata['field_key']);
        $this->assertSame(['contact', 'payment'], $metadata['error_sections']);
        $this->assertSame(['contact_email', 'payment_type'], $metadata['error_fields']);
        $this->assertSame(['required', 'email'], $metadata['validation_error_codes']);
        $this->assertArrayNotHasKey('field_value', $metadata);
        $this->assertArrayNotHasKey('email', $metadata);
        $this->assertArrayNotHasKey('nip', $metadata);
    }
}
