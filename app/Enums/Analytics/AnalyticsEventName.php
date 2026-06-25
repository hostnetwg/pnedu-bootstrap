<?php

namespace App\Enums\Analytics;

enum AnalyticsEventName: string
{
    case CampaignShortLinkVisit = 'campaign_short_link_visit';
    case CampaignRedirectResolved = 'campaign_redirect_resolved';
    case UtmCaptured = 'utm_captured';
    case CourseDescriptionViewed = 'course_description_viewed';
    case OrderFormViewed = 'order_form_viewed';
    case OrderFormSubmitAttempted = 'order_form_submit_attempted';
    case OrderFormValidationFailed = 'order_form_validation_failed';
    case FormOrderCreated = 'form_order_created';
    case OnlinePaymentSelected = 'online_payment_selected';
    case DeferredInvoiceSelected = 'deferred_invoice_selected';
    case PaymentOrderCreated = 'payment_order_created';
    case PaymentStatusChanged = 'payment_status_changed';
    case InvoiceCreated = 'invoice_created';

    // Etap B1 — eventy JS formularza zamówienia (źródło: przeglądarka, bez wartości pól).
    case OrderFormStarted = 'order_form_started';
    case OrderFormSectionInteracted = 'order_form_section_interacted';
    case OrderFormCtaClicked = 'order_form_cta_clicked';
    case OrderFormSubmitClicked = 'order_form_submit_clicked';

    public function category(): AnalyticsCategory
    {
        return match ($this) {
            self::CampaignShortLinkVisit,
            self::CampaignRedirectResolved,
            self::UtmCaptured => AnalyticsCategory::Campaign,
            self::CourseDescriptionViewed => AnalyticsCategory::Landing,
            self::OrderFormViewed,
            self::OrderFormSubmitAttempted,
            self::OrderFormStarted,
            self::OrderFormSectionInteracted,
            self::OrderFormCtaClicked,
            self::OrderFormSubmitClicked => AnalyticsCategory::OrderForm,
            self::OrderFormValidationFailed => AnalyticsCategory::Validation,
            self::FormOrderCreated => AnalyticsCategory::Conversion,
            self::OnlinePaymentSelected,
            self::PaymentOrderCreated,
            self::PaymentStatusChanged => AnalyticsCategory::Payment,
            self::DeferredInvoiceSelected,
            self::InvoiceCreated => AnalyticsCategory::Invoice,
        };
    }

    /**
     * Eventy dozwolone z publicznego endpointu JS (POST /analytics/client-events).
     * Tylko te 4 nazwy mogą pochodzić z przeglądarki — reszta wyłącznie z backendu.
     *
     * @return list<self>
     */
    public static function clientJsEvents(): array
    {
        return [
            self::OrderFormStarted,
            self::OrderFormSectionInteracted,
            self::OrderFormCtaClicked,
            self::OrderFormSubmitClicked,
        ];
    }

    public static function isClientJsEvent(string $eventName): bool
    {
        foreach (self::clientJsEvents() as $event) {
            if ($event->value === $eventName) {
                return true;
            }
        }

        return false;
    }
}
