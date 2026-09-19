<?php

namespace App\Services;

use App\Models\Participant;
use App\Models\ParticipantLiveAccess;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Zapis „jest teraz na /transmisja” do pneadm (panel live).
 * Heartbeat już leci co ~25 s — UPDATE idzie tylko gdy znacznik jest starszy niż 20 s.
 */
class LiveEmbedPresenceService
{
    public const PERSIST_INTERVAL_SECONDS = 20;

    public function touch(Participant $participant): void
    {
        if (! $this->columnAvailable()) {
            return;
        }

        $participantId = (int) $participant->id;
        if ($participantId <= 0) {
            return;
        }

        try {
            $cutoff = now()->subSeconds(self::PERSIST_INTERVAL_SECONDS);
            ParticipantLiveAccess::query()
                ->where('participant_id', $participantId)
                ->where(function ($query) use ($cutoff) {
                    $query->whereNull('embed_last_seen_at')
                        ->orWhere('embed_last_seen_at', '<', $cutoff);
                })
                ->update(['embed_last_seen_at' => now()]);
        } catch (\Throwable $e) {
            Log::warning('LiveEmbedPresenceService: nie udało się zapisać obecności', [
                'participant_id' => $participantId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function clear(Participant $participant): void
    {
        if (! $this->columnAvailable()) {
            return;
        }

        $participantId = (int) $participant->id;
        if ($participantId <= 0) {
            return;
        }

        try {
            ParticipantLiveAccess::query()
                ->where('participant_id', $participantId)
                ->update(['embed_last_seen_at' => null]);
        } catch (\Throwable $e) {
            Log::warning('LiveEmbedPresenceService: nie udało się wyczyścić obecności', [
                'participant_id' => $participantId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function columnAvailable(): bool
    {
        try {
            return Schema::connection('pneadm')->hasColumn('participant_live_access', 'embed_last_seen_at');
        } catch (\Throwable) {
            return false;
        }
    }
}
