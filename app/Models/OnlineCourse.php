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
        'training_scope',
        'offer_description_html',
        'instructor_id',
        'image',
        'is_active',
        'visible_in_dashboard',
        'internal_notes',
        'legacy_publigo_product_id',
        'certificate_download_status',
        'certificate_template_id',
        'certificate_format',
        'certificate_issue_date',
        'certificate_duration_minutes',
        'certificate_collect_birth_data',
        'certificate_birth_data_required',
        'certificate_completion_threshold_percent',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'visible_in_dashboard' => 'boolean',
        'certificate_issue_date' => 'date',
        'certificate_duration_minutes' => 'integer',
        'certificate_collect_birth_data' => 'boolean',
        'certificate_birth_data_required' => 'boolean',
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

    public function certificatesEnabledForDownload(): bool
    {
        return ($this->certificate_download_status ?? '') === 'download_enabled';
    }

    /** Pełny URL obrazu w publicznym storage pneadm (miniatury na pnedu.pl). */
    public function publicImageUrl(): ?string
    {
        return PneadmMedia::url($this->image);
    }
}
