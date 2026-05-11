<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnlineCourseEnrollment extends Model
{
    protected $connection = 'pneadm';

    protected $table = 'online_course_enrollments';

    protected $fillable = [
        'online_course_id',
        'email',
        'first_name',
        'last_name',
        'access_expires_at',
        'access_source',
        'notes',
    ];

    protected $casts = [
        'access_expires_at' => 'datetime',
    ];

    protected function setEmailAttribute(mixed $value): void
    {
        $this->attributes['email'] = is_string($value) ? strtolower(trim($value)) : '';
    }

    public static function normalizeEmail(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }
        $t = strtolower(trim($email));

        return $t === '' ? null : $t;
    }

    public function onlineCourse(): BelongsTo
    {
        return $this->belongsTo(OnlineCourse::class);
    }

    public function lessonCompletions(): HasMany
    {
        return $this->hasMany(OnlineCourseLessonCompletion::class, 'online_course_enrollment_id');
    }

    public function lessonNotes(): HasMany
    {
        return $this->hasMany(OnlineCourseLessonNote::class, 'online_course_enrollment_id');
    }

    public function hasExpiredAccess(): bool
    {
        if (! $this->access_expires_at) {
            return false;
        }

        $now = Carbon::now('UTC');
        $expiresAt = $this->access_expires_at->copy()->setTimezone('UTC');

        return $expiresAt->lt($now);
    }

    public function emailMatchesUser(string $userEmail): bool
    {
        $a = self::normalizeEmail($this->email);
        $b = self::normalizeEmail($userEmail);

        return $a !== null && $b !== null && $a === $b;
    }
}
