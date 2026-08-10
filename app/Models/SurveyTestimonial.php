<?php

namespace App\Models;

use App\Support\SurveyAvatarPresets;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SurveyTestimonial extends Model
{
    protected $connection = 'pneadm';

    protected $table = 'survey_testimonials';

    protected $guarded = [];

    protected $casts = [
        'rating' => 'integer',
        'publish_consent' => 'boolean',
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
        'display_order' => 'integer',
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->where('publish_consent', true)
            ->orderByDesc('is_featured')
            ->orderBy('display_order')
            ->orderByDesc('created_at');
    }

    public function subtitle(): string
    {
        return collect([$this->author_role, $this->author_city])
            ->filter(fn ($v) => filled($v))
            ->implode(', ');
    }

    public function hasAvatar(): bool
    {
        if ($this->avatar_type === 'none') {
            return false;
        }

        if ($this->avatar_type === 'upload') {
            return filled($this->avatar_path);
        }

        $key = SurveyAvatarPresets::migrateLegacyKey($this->avatar_preset);

        return $key !== null;
    }

    public function avatarUrl(): ?string
    {
        if (! $this->hasAvatar()) {
            return null;
        }

        if ($this->avatar_type === 'upload' && filled($this->avatar_path)) {
            return asset(ltrim((string) $this->avatar_path, '/'));
        }

        $key = SurveyAvatarPresets::migrateLegacyKey($this->avatar_preset);

        return $key ? SurveyAvatarPresets::url($key) : null;
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/u', trim((string) $this->author_name)) ?: [];
        $letters = collect($parts)
            ->filter()
            ->take(2)
            ->map(fn (string $p) => mb_strtoupper(mb_substr($p, 0, 1)))
            ->implode('');

        return $letters !== '' ? $letters : '?';
    }
}
