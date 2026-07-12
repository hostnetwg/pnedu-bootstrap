<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Odczyt ustawień widoczności opcji płatności (tabela w bazie pneadm).
 * Zapis odbywa się w panelu adm (pneadm-bootstrap).
 */
class PaymentDisplayOption extends Model
{
    protected $connection = 'pneadm';

    protected $table = 'payment_display_options';

    /**
     * Konta deweloperskie uprawnione do auto-wypełniania formularza zamówienia (gdy włączona opcja developers_only).
     *
     * @var list<string>
     */
    public const ORDER_FORM_AUTO_FILL_DEVELOPER_EMAILS = [
        'waldemar.grabowski@hostnet.pl',
        'luman0599@gmail.com',
    ];

    /** Po tym czasie na produkcji sama wyłącza się opcja bez ograniczeń e-mail (bezpiecznik). */
    public const UNRESTRICTED_AUTO_FILL_PRODUCTION_TTL_MINUTES = 1;

    protected $casts = [
        'show_pay_publigo' => 'boolean',
        'show_pay_online' => 'boolean',
        'show_deferred_order' => 'boolean',
        'show_order_form' => 'boolean',
        'show_order_form_v2' => 'boolean',
        'show_order_form_alt' => 'boolean',
        'order_form_auto_fill_test_data' => 'boolean',
        'order_form_auto_fill_test_data_enabled_at' => 'datetime',
        'order_form_auto_fill_test_data_developers_only' => 'boolean',
        'default_post_end_access_duration_value' => 'integer',
    ];

    /**
     * Zwraca tablicę flag widoczności (dla widoku). W razie błędu lub braku tabeli – wszystkie true.
     */
    public static function getForCoursePage(): array
    {
        try {
            $row = self::first();
            if ($row) {
                $row = self::expireUnrestrictedAutoFillIfNeeded($row);

                return [
                    'show_pay_publigo' => (bool) $row->show_pay_publigo,
                    'show_pay_online' => (bool) $row->show_pay_online,
                    'show_deferred_order' => (bool) $row->show_deferred_order,
                    'show_order_form' => (bool) $row->show_order_form,
                    'show_order_form_v2' => (bool) ($row->show_order_form_v2 ?? false),
                    'default_signup_order_form_variant' => \App\Support\OrderFormVariant::normalize(
                        $row->default_signup_order_form_variant ?? null
                    ),
                    'show_order_form_alt' => (bool) $row->show_order_form_alt,
                    'order_form_auto_fill_test_data' => (bool) ($row->order_form_auto_fill_test_data ?? false),
                    'order_form_auto_fill_test_data_developers_only' => (bool) ($row->order_form_auto_fill_test_data_developers_only ?? false),
                    'developer_online_payment_test_enabled' => (bool) ($row->developer_online_payment_test_enabled ?? false),
                    'developer_online_payment_sandbox_gateway' => (bool) ($row->developer_online_payment_sandbox_gateway ?? true),
                    'default_post_end_access_duration_value' => (int) ($row->default_post_end_access_duration_value ?? 2),
                    'default_post_end_access_duration_unit' => (string) ($row->default_post_end_access_duration_unit ?? 'months'),
                ];
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return [
            'show_pay_publigo' => true,
            'show_pay_online' => true,
            'show_deferred_order' => true,
            'show_order_form' => true,
            'show_order_form_v2' => false,
            'default_signup_order_form_variant' => \App\Support\OrderFormVariant::LEGACY,
            'show_order_form_alt' => true,
            'order_form_auto_fill_test_data' => false,
            'order_form_auto_fill_test_data_developers_only' => false,
            'developer_online_payment_test_enabled' => false,
            'developer_online_payment_sandbox_gateway' => true,
            'default_post_end_access_duration_value' => 2,
            'default_post_end_access_duration_unit' => 'months',
        ];
    }

    public static function unrestrictedAutoFillShouldExpire(): bool
    {
        return app()->environment('production');
    }

    public static function isUnrestrictedAutoFillExpired(?Carbon $enabledAt): bool
    {
        if ($enabledAt instanceof Carbon) {
            return $enabledAt->lt(now()->subMinutes(self::UNRESTRICTED_AUTO_FILL_PRODUCTION_TTL_MINUTES));
        }

        return true;
    }

    /**
     * Na produkcji wyłącza opcję bez ograniczeń e-mail po UNRESTRICTED_AUTO_FILL_PRODUCTION_TTL_MINUTES.
     * Opcja developers_only nie ma auto-wygaśnięcia.
     */
    public static function expireUnrestrictedAutoFillIfNeeded(self $row): self
    {
        if (! (bool) ($row->order_form_auto_fill_test_data ?? false)) {
            return $row;
        }

        if (! self::unrestrictedAutoFillShouldExpire()) {
            return $row;
        }

        if (! self::isUnrestrictedAutoFillExpired($row->order_form_auto_fill_test_data_enabled_at)) {
            return $row;
        }

        $row->forceFill([
            'order_form_auto_fill_test_data' => false,
            'order_form_auto_fill_test_data_enabled_at' => null,
        ])->save();

        return $row;
    }

    /**
     * Czy formularz zamówienia ma być w trybie testowym (przycisk „Wypełnij dane testowe”).
     * Obie opcje w adm (developers_only i unrestricted) — tylko przycisk, bez auto-wypełnienia pól przy wejściu.
     * Unrestricted: także dla niezalogowanych. Developers_only: wymaga zalogowania i adresu z listy.
     */
    public static function isOrderFormTestModeEnabled(array $options, ?\Illuminate\Contracts\Auth\Authenticatable $user): bool
    {
        if ($options['order_form_auto_fill_test_data'] ?? false) {
            return true;
        }

        if (! ($options['order_form_auto_fill_test_data_developers_only'] ?? false)) {
            return false;
        }

        if (! $user || ! filled($user->email)) {
            return false;
        }

        $email = mb_strtolower(trim((string) $user->email));

        foreach (self::ORDER_FORM_AUTO_FILL_DEVELOPER_EMAILS as $allowed) {
            if ($email === mb_strtolower($allowed)) {
                return true;
            }
        }

        return false;
    }
}
