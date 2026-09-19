<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseOnlineDetail extends Model
{
    use HasFactory;

    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'pneadm';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'course_online_details';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'course_id',
        'platform',
        'meeting_link',
        'meeting_password',
        'clickmeeting_event_id',
        'clickmeeting_join_enabled',
        'embed_on_pnedu',
        'live_bar_attendance_enabled',
        'live_bar_materials_enabled',
        'live_bar_survey_enabled',
        'live_bar_certificate_enabled',
        'live_offer_course_id',
        'live_offer_enabled',
    ];

    protected $casts = [
        'clickmeeting_join_enabled' => 'boolean',
        'embed_on_pnedu' => 'boolean',
        'live_bar_attendance_enabled' => 'boolean',
        'live_bar_materials_enabled' => 'boolean',
        'live_bar_survey_enabled' => 'boolean',
        'live_bar_certificate_enabled' => 'boolean',
        'live_offer_course_id' => 'integer',
        'live_offer_enabled' => 'boolean',
    ];

    /**
     * Course online detail belongs to a course.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }
}
