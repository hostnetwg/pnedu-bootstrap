<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParticipantLiveAccess extends Model
{
    protected $connection = 'pneadm';

    protected $table = 'participant_live_access';

    protected $fillable = [
        'participant_id',
        'course_id',
        'form_order_id',
        'platform',
        'clickmeeting_event_id',
        'access_type',
        'room_url',
        'token',
        'status',
        'message',
        'synced_at',
        'expires_at',
    ];

    protected $casts = [
        'access_type' => 'integer',
        'synced_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }
}
