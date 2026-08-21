<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Jedna aktywna sesja transmisji na participant_id (anty-sharing między urządzeniami).
 * Właściciel = Laravel session id (ta sama przeglądarka może odświeżyć; drugi PC — nie).
 */
class LiveTransmissionPresenceService
{
    public const ERROR_BUSY = 'Transmisja jest już otwarta na innym urządzeniu lub w innej przeglądarce (albo w innej karcie tego komputera). '
        .'Zamknij tam okno transmisji albo nieużywane karty/okna przeglądarki z pnedu.pl, '
        .'poczekaj ok. minutę i spróbuj ponownie.';

    /**
     * @return array{ok: bool, error?: string}
     */
    public function acquire(int $participantId, string $ownerSessionId, int $ttlSeconds): array
    {
        $ownerSessionId = trim($ownerSessionId);
        if ($participantId <= 0 || $ownerSessionId === '') {
            return ['ok' => false, 'error' => 'Nieprawidłowa sesja transmisji.'];
        }

        $key = $this->cacheKey($participantId);
        $now = now()->getTimestamp();
        $payload = [
            'owner' => $ownerSessionId,
            'updated_at' => $now,
        ];

        $existing = Cache::get($key);
        if (is_array($existing)) {
            $existingOwner = trim((string) ($existing['owner'] ?? ''));
            if ($existingOwner !== '' && $existingOwner !== $ownerSessionId) {
                return ['ok' => false, 'error' => self::ERROR_BUSY];
            }
        }

        Cache::put($key, $payload, $ttlSeconds);

        return ['ok' => true];
    }

    public function heartbeat(int $participantId, string $ownerSessionId, int $ttlSeconds): bool
    {
        $ownerSessionId = trim($ownerSessionId);
        if ($participantId <= 0 || $ownerSessionId === '') {
            return false;
        }

        $key = $this->cacheKey($participantId);
        $existing = Cache::get($key);
        if (! is_array($existing)) {
            return false;
        }

        if (trim((string) ($existing['owner'] ?? '')) !== $ownerSessionId) {
            return false;
        }

        Cache::put($key, [
            'owner' => $ownerSessionId,
            'updated_at' => now()->getTimestamp(),
        ], $ttlSeconds);

        return true;
    }

    public function release(int $participantId, string $ownerSessionId): void
    {
        $ownerSessionId = trim($ownerSessionId);
        if ($participantId <= 0 || $ownerSessionId === '') {
            return;
        }

        $key = $this->cacheKey($participantId);
        $existing = Cache::get($key);
        if (! is_array($existing)) {
            return;
        }

        if (trim((string) ($existing['owner'] ?? '')) !== $ownerSessionId) {
            return;
        }

        Cache::forget($key);
    }

    public function ttlSeconds(bool $mobile): int
    {
        if ($mobile) {
            return max(60, (int) config('services.clickmeeting.live_presence_mobile_ttl_seconds', 10800));
        }

        return max(30, (int) config('services.clickmeeting.live_presence_ttl_seconds', 90));
    }

    public function heartbeatIntervalSeconds(): int
    {
        $ttl = $this->ttlSeconds(false);
        $interval = (int) config('services.clickmeeting.live_presence_heartbeat_seconds', 25);

        return max(10, min($interval, (int) floor($ttl / 2)));
    }

    private function cacheKey(int $participantId): string
    {
        return 'live_tx_presence:'.$participantId;
    }
}
