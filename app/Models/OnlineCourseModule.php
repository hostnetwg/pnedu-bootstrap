<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnlineCourseModule extends Model
{
    protected $connection = 'pneadm';

    protected $table = 'online_course_modules';

    protected $fillable = [
        'online_course_id',
        'title',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function onlineCourse(): BelongsTo
    {
        return $this->belongsTo(OnlineCourse::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(OnlineCourseLesson::class)->orderBy('sort_order')->orderBy('id');
    }
}
