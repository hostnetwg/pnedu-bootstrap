<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Odczyt singletonu ustawień ankiet z bazy pneadm.
 */
class SurveySetting extends Model
{
    public const SINGLETON_ID = 1;

    public const SETTINGS_CACHE_KEY = 'pnedu_survey_settings_singleton';

    public const SETTINGS_CACHE_TTL_SECONDS = 120;

    protected $connection = 'pneadm';

    protected $table = 'survey_settings';

    protected $guarded = [];

    protected $casts = [
        'auto_open_offset_hours' => 'integer',
        'auto_close_after_days' => 'integer',
        'default_is_anonymous' => 'boolean',
        'allow_multiple_responses' => 'boolean',
    ];

    public static function getSettings(): self
    {
        return Cache::remember(
            self::SETTINGS_CACHE_KEY,
            self::SETTINGS_CACHE_TTL_SECONDS,
            function () {
                try {
                    $row = self::query()->find(self::SINGLETON_ID);
                    if ($row) {
                        return $row;
                    }
                } catch (\Throwable) {
                    // brak tabeli / połączenia
                }

                $fallback = new self([
                    'allow_multiple_responses' => false,
                    'default_is_anonymous' => true,
                ]);
                $fallback->id = self::SINGLETON_ID;

                return $fallback;
            }
        );
    }

    public function allowsMultipleResponses(): bool
    {
        return (bool) ($this->attributes['allow_multiple_responses'] ?? false);
    }
}
