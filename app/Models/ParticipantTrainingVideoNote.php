<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParticipantTrainingVideoNote extends Model
{
    protected $connection = 'pneadm';

    protected $table = 'participant_training_video_notes';

    protected $fillable = [
        'participant_id',
        'course_video_id',
        'body',
    ];

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function courseVideo(): BelongsTo
    {
        return $this->belongsTo(CourseVideo::class, 'course_video_id');
    }
}
