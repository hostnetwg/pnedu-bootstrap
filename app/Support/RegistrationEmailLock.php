<?php

namespace App\Support;

/**
 * Sprawdza, czy adres na /register pochodzi z listy uczestników i nie został podmieniony.
 * Podpis musi zgadzać się z pneadm App\Support\PneduRegistrationLink.
 */
class RegistrationEmailLock
{
    public static function matches(?string $email, ?string $lock): bool
    {
        $normalized = strtolower(trim((string) $email));
        $lock = trim((string) $lock);

        if ($normalized === '' || $lock === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $normalized, (string) config('services.pneadm.api_token'));

        return hash_equals($expected, $lock);
    }
}
