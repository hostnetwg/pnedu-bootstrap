<?php

namespace App\Support;

use App\Models\PaymentDisplayOption;

/**
 * Symboliczna kwota płatności online (5 PLN) i wybór bramki sandbox/produkcja
 * wyłącznie dla kont deweloperskich — gdy włączone w panelu adm.
 */
class DeveloperOnlinePaymentTest
{
    public const SYMBOLIC_AMOUNT_PLN = 5.0;

    public static function isDeveloperAccount(?\Illuminate\Contracts\Auth\Authenticatable $user): bool
    {
        if (! $user || ! filled($user->email)) {
            return false;
        }

        $email = mb_strtolower(trim((string) $user->email));

        foreach (PaymentDisplayOption::ORDER_FORM_AUTO_FILL_DEVELOPER_EMAILS as $allowed) {
            if ($email === mb_strtolower($allowed)) {
                return true;
            }
        }

        return false;
    }

    public static function isEnabledInSettings(array $options): bool
    {
        return (bool) ($options['developer_online_payment_test_enabled'] ?? false);
    }

    /**
     * Czy zalogowane konto deweloperskie ma zapłacić symboliczną kwotę.
     */
    public static function shouldApplySymbolicAmount(array $options, ?\Illuminate\Contracts\Auth\Authenticatable $user): bool
    {
        return self::isEnabledInSettings($options) && self::isDeveloperAccount($user);
    }

    public static function resolveCheckoutAmount(float $normalAmount, array $options, ?\Illuminate\Contracts\Auth\Authenticatable $user): float
    {
        if (! self::shouldApplySymbolicAmount($options, $user)) {
            return $normalAmount;
        }

        return self::SYMBOLIC_AMOUNT_PLN;
    }

    /**
     * null = użyj domyślnej konfiguracji z .env (PAYU_SANDBOX / PAYNOW_SANDBOX).
     */
    public static function sandboxGatewayOverride(array $options, ?\Illuminate\Contracts\Auth\Authenticatable $user): ?bool
    {
        if (! self::isEnabledInSettings($options) || ! self::isDeveloperAccount($user)) {
            return null;
        }

        return (bool) ($options['developer_online_payment_sandbox_gateway'] ?? true);
    }
}
