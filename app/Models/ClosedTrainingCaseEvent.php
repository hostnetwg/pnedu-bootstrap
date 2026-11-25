<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClosedTrainingCaseEvent extends Model
{
    public const TYPE_CREATED = 'created';

    protected $connection = 'pneadm';

    protected $table = 'closed_training_case_events';

    protected $fillable = [
        'closed_training_case_id',
        'user_id',
        'event_type',
        'body',
        'note_channel',
        'note_channel_other',
        'note_party',
        'note_party_name',
        'happened_at',
    ];

    protected $casts = [
        'happened_at' => 'datetime',
    ];
}
