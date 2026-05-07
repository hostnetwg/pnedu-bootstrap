<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Odczyt z PNEADM: zewnętrzne ankiety przy szkoleniu (wyłącznie bramka publiczna).
 *
 * @property int $id
 * @property int $course_id
 * @property string $public_token
 * @property string $url
 * @property string|null $title
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $opens_at
 * @property \Illuminate\Support\Carbon|null $closes_at
 */
class PneadmCourseSurveyLink extends Model
{
    protected $connection = 'pneadm';

    protected $table = 'course_survey_links';

    /** @var list<string> */
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'opens_at' => 'datetime',
        'closes_at' => 'datetime',
        'order' => 'integer',
    ];

    public function isAvailableNow(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->opens_at && $now->lt($this->opens_at)) {
            return false;
        }

        if ($this->closes_at && $now->gt($this->closes_at)) {
            return false;
        }

        return true;
    }
}
