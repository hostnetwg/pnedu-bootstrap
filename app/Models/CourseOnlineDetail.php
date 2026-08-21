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
    ];

    protected $casts = [
        'clickmeeting_join_enabled' => 'boolean',
        'embed_on_pnedu' => 'boolean',
    ];

    /**
     * Course online detail belongs to a course.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }
}
