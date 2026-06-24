<?php

namespace App\Models;

use App\Support\PneadmMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OnlineCourse extends Model
{
    use SoftDeletes;

    protected $connection = 'pneadm';

    protected $table = 'online_courses';

    protected $fillable = [
        'slug',
        'title',
        'description',
        'offer_description_html',
        'instructor_id',
        'image',
        'is_active',
        'visible_in_dashboard',
        'internal_notes',
        'legacy_publigo_product_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'visible_in_dashboard' => 'boolean',
    ];

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    public function modules(): HasMany
    {
        return $this->hasMany(OnlineCourseModule::class)->orderBy('sort_order')->orderBy('id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(OnlineCourseEnrollment::class);
    }

    public function modulesWithPublishedLessons(): HasMany
    {
        return $this->modules()
            ->with(['lessons' => fn ($q) => $q->where('is_published', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->with(['embeds', 'resourceLinks']),
            ])
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /** Pełny URL obrazu w publicznym storage pneadm (miniatury na pnedu.pl). */
    public function publicImageUrl(): ?string
    {
        return PneadmMedia::url($this->image);
    }
}
