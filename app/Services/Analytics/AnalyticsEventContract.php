<?php

namespace App\Services\Analytics;

class AnalyticsEventContract
{
    public const SCHEMA_VERSION = 2;

    /** @var list<string> */
    public const SECTION_KEYS = [
        'contact',
        'invoice_buyer',
        'invoice_recipient',
        'participants',
        'payment',
        'consents',
        'submit',
        // Legacy aliases used by the existing order form markup.
        'buyer_data',
        'recipient_data',
        'payment_method',
        'invoice',
        'summary',
    ];

    /** @var list<string> */
    public const CTA_KEYS = [
        'add_participant',
        'remove_participant',
        'select_online_payment',
        'select_deferred_invoice',
        'back_to_course',
        'submit_order',
    ];

    /** @var list<string> */
    public const TRIGGERS = [
        'first_interaction',
        'field_change',
        'section_click',
        'payment_select',
        'cta_click',
        'page_focus',
        'visibility',
        'client_validation',
    ];

    /** @var list<string> */
    public const FIELD_KEYS = [
        'contact_name',
        'contact_phone',
        'contact_email',
        'buyer_type',
        'buyer_nip',
        'buyer_address',
        'buyer_postcode',
        'buyer_city',
        'recipient_nip',
        'recipient_address',
        'recipient_postcode',
        'recipient_city',
        'participant_first_name',
        'participant_last_name',
        'participant_email',
        'payment_type',
        'payment_terms',
        'payment_gateway',
        'invoice_notes',
        'consent',
    ];

    /** @var list<string> */
    public const FIELD_TYPES = [
        'text',
        'email',
        'tel',
        'number',
        'radio',
        'checkbox',
        'select',
        'textarea',
        'hidden',
        'unknown',
    ];

    /** @var list<string> */
    public const FIELD_SOURCES = [
        'manual',
        'gus',
        'copied',
        'browser_autofill',
        'unknown',
    ];

    /** @var list<string> */
    public const GUS_TARGETS = ['buyer', 'recipient'];

    /** @var list<string> */
    public const GUS_ERROR_TYPES = [
        'validation_error',
        'not_found',
        'gus_unavailable',
        'timeout',
        'server_error',
        'rate_limit',
        'unknown',
    ];

    /** @var list<string> */
    public const GUS_RESPONSE_SOURCES = ['gus', 'cache', 'unknown'];

    /** @var list<string> */
    public const GUS_RESULT_TYPES = ['exact_match', 'partial_match', 'unknown'];

    /** @var list<string> */
    public const TRAFFIC_CHANNELS = [
        'newsletter',
        'paid_social',
        'organic_search',
        'direct',
        'referral',
        'internal_site',
        'paid_search',
        'organic_social',
        'unknown',
        'other',
    ];

    /** @var list<string> */
    public const LAST_ACTIVITY_TYPES = [
        'field_changed',
        'section_started',
        'section_completed',
        'section_viewed',
        'first_interaction',
        'form_visible',
        'form_submit_clicked',
        'client_validation_failed',
        'gus_lookup_clicked',
        'gus_data_applied',
        'gus_manual_fallback_started',
        'unknown',
    ];

    /** @var list<string> */
    public const CLIENT_EVENT_NAMES = [
        'order_form_started',
        'order_form_section_interacted',
        'order_form_cta_clicked',
        'order_form_submit_clicked',
        'form_visible',
        'form_first_interaction',
        'form_section_viewed',
        'form_section_started',
        'form_section_completed',
        'form_field_changed',
        'form_submit_clicked',
        'client_validation_failed',
        'form_last_activity',
        'gus_lookup_clicked',
        'gus_data_applied',
        'form_field_edited_after_gus',
        'gus_manual_fallback_started',
    ];

    /** @var array<string, string> */
    public const LEGACY_EVENT_ALIASES = [
        'order_form_started' => 'form_first_interaction',
        'order_form_section_interacted' => 'form_section_started',
        'order_form_submit_clicked' => 'form_submit_clicked',
        'order_form_submit_attempted' => 'server_submit_attempted',
        'order_form_validation_failed' => 'server_validation_failed',
        'form_order_created' => 'order_created',
    ];

    /** @var array<string, list<string>> */
    private const EVENT_PROPERTIES = [
        'form_visible' => ['seconds_from_page_load'],
        'form_first_interaction' => ['first_interaction_type', 'first_section_key', 'first_field_key', 'seconds_from_page_load', 'trigger'],
        'form_section_viewed' => ['section_key', 'seconds_from_page_load'],
        'form_section_started' => ['section_key', 'first_field_key', 'trigger'],
        'form_section_completed' => ['section_key', 'required_fields_count', 'completed_fields_count'],
        'form_field_changed' => ['section_key', 'field_key', 'field_type', 'source', 'has_value', 'seconds_from_page_load'],
        'form_submit_clicked' => ['completed_sections_count', 'visible_validation_errors_count', 'selected_payment_method', 'seconds_from_page_load', 'trigger'],
        'client_validation_failed' => ['errors_count', 'error_sections', 'error_fields', 'first_error_section', 'first_error_field', 'validation_error_codes'],
        'form_last_activity' => ['last_activity_type', 'last_event_name', 'last_section_key', 'last_field_key', 'completed_sections_count'],
        'gus_lookup_clicked' => ['target', 'section_key', 'nip_present', 'nip_format_valid_client', 'seconds_from_page_load'],
        'gus_data_applied' => ['target', 'section_key', 'fields_applied_count', 'overwritten_manual_fields_count', 'seconds_after_gus_success'],
        'form_field_edited_after_gus' => ['gus_target', 'section_key', 'field_key', 'field_type', 'seconds_after_gus_success'],
        'gus_manual_fallback_started' => ['target', 'section_key', 'first_field_key', 'seconds_after_gus_error'],
        'gus_lookup_started' => ['target', 'section_key', 'started_at'],
        'gus_lookup_success' => ['target', 'section_key', 'latency_ms', 'fields_returned_count', 'response_source', 'result_type'],
        'gus_lookup_error' => ['target', 'section_key', 'latency_ms', 'error_type', 'http_status', 'retry_possible'],
    ];

    public static function canonicalEventName(string $eventName): string
    {
        return self::LEGACY_EVENT_ALIASES[$eventName] ?? $eventName;
    }

    public static function isClientEvent(string $eventName): bool
    {
        return in_array($eventName, self::CLIENT_EVENT_NAMES, true);
    }

    /**
     * @return list<string>
     */
    public static function allowedPropertiesFor(string $eventName): array
    {
        $canonical = self::canonicalEventName($eventName);

        return self::EVENT_PROPERTIES[$canonical] ?? [];
    }
}
