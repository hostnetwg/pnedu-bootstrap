<?php

namespace App\Models;

use App\Enums\Analytics\AnalyticsMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Odczyt runtime override trybów analityki z bazy pneadm (tabela analytics_settings).
 *
 * Zapis odbywa się wyłącznie w panelu adm (pneadm). Tutaj tylko odczyt — wzorzec jak
 * PaymentDisplayOption (connection 'pneadm'). .env ANALYTICS_ENABLED=false w pnedu
 * pozostaje hard kill switch (obsługiwany w AnalyticsModeResolver przed odczytem override).
 */
class AnalyticsSetting extends Model
{
    public const SINGLETON_ID = 1;

    public const SETTINGS_CACHE_KEY = 'analytics_settings_singleton';

    public const SETTINGS_CACHE_TTL_SECONDS = 60;

    protected $connection = 'pneadm';

    protected $table = 'analytics_settings';

    protected $casts = [
        'enabled_override' => 'boolean',
        'updated_by' => 'integer',
    ];

    /**
     * @return list<string>
     */
    public static function allowedModes(): array
    {
        return array_map(static fn (AnalyticsMode $mode): string => $mode->value, AnalyticsMode::cases());
    }

    public static function forgetSettingsCache(): void
    {
        Cache::forget(self::SETTINGS_CACHE_KEY);
    }

    public static function enabledOverride(): ?bool
    {
        $value = self::getSettings()?->enabled_override;

        return $value === null ? null : (bool) $value;
    }

    public static function defaultModeOverride(): ?string
    {
        $value = self::getSettings()?->default_mode_override;

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return in_array($value, self::allowedModes(), true) ? $value : null;
    }

    /**
     * Fail-safe: w razie błędu odczytu z pneadm zwraca null (resolver użyje config jako fallback).
     */
    private static function getSettings(): ?self
    {
        return Cache::remember(
            self::SETTINGS_CACHE_KEY,
            self::SETTINGS_CACHE_TTL_SECONDS,
            static function (): ?self {
                try {
                    return self::query()->find(self::SINGLETON_ID) ?? self::query()->first();
                } catch (\Throwable) {
                    return null;
                }
            },
        );
    }
}
