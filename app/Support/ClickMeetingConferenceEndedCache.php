<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;

/**
 * Flaga „prowadzący zakończył dla wszystkich” — ustawiana z polla /transmisja, odczyt na /po-szkoleniu.
 */
class ClickMeetingConferenceEndedCache
{
    public static function key(string $eventId): string
    {
        return 'cm:conference_ended_by_host:'.trim($eventId);
    }

    public static function mark(string $eventId, CarbonInterface $expiresAt): void
    {
        $eventId = trim($eventId);
        if ($eventId === '') {
            return;
        }

        Cache::put(self::key($eventId), true, $expiresAt);
    }

    public static function wasEndedByHost(string $eventId): bool
    {
        $eventId = trim($eventId);
        if ($eventId === '') {
            return false;
        }

        return (bool) Cache::get(self::key($eventId), false);
    }
}
