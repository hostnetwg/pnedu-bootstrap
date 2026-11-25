<?php

namespace App\Support;

class PhoneDisplay
{
    public static function format(?string $raw): string
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return '';
        }

        $digits = preg_replace('/\D+/', '', $raw) ?: '';
        if (str_starts_with($digits, '0048')) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) === 9) {
            $digits = '48'.$digits;
        }

        if (preg_match('/^48(\d{3})(\d{3})(\d{3})$/', $digits, $matches)) {
            return '+48 '.$matches[1].' '.$matches[2].' '.$matches[3];
        }

        return $raw;
    }

    public static function telHref(?string $raw): ?string
    {
        $formatted = self::format($raw);
        if ($formatted === '') {
            return null;
        }

        if (preg_match('/^\+48 \d{3} \d{3} \d{3}$/', $formatted)) {
            return 'tel:+'.preg_replace('/\D+/', '', $formatted);
        }

        $digits = preg_replace('/\D+/', '', (string) $raw) ?: '';

        return $digits === '' ? null : 'tel:'.$digits;
    }
}
