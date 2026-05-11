<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnlineCourseLessonCompletion extends Model
{
    protected $connection = 'pneadm';

    protected $table = 'online_course_lesson_completions';

    protected $fillable = [
        'online_course_enrollment_id',
        'online_course_lesson_id',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(OnlineCourseEnrollment::class, 'online_course_enrollment_id');
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(OnlineCourseLesson::class, 'online_course_lesson_id');
    }
}
