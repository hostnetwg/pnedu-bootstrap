<?php

namespace App\Models;

use App\Support\PneadmMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingOffer extends Model
{
    public const PRICE_MODE_FIXED = 'fixed';

    protected $connection = 'pneadm';

    protected $table = 'training_offers';

    protected $fillable = [
        'title',
        'slug',
        'summary',
        'description_html',
        'scope',
        'audience',
        'price_mode',
        'price_amount',
        'instructor_id',
        'image',
        'default_course_category',
        'is_active',
        'show_on_pnedu',
        'featured_on_homepage',
        'sort_order',
        'meta_title',
        'meta_description',
        'internal_notes',
    ];

    protected $casts = [
        'price_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'show_on_pnedu' => 'boolean',
        'featured_on_homepage' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class, 'instructor_id');
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('show_on_pnedu', true)
            ->whereNull('deleted_at');
    }

    public function formattedPrice(): string
    {
        if ($this->price_mode !== self::PRICE_MODE_FIXED || $this->price_amount === null) {
            return 'Cena ustalana indywidualnie';
        }

        return number_format((float) $this->price_amount, 2, ',', ' ').' PLN brutto';
    }

    public function publicImageUrl(): ?string
    {
        return PneadmMedia::url($this->image);
    }

    public function seoTitle(): string
    {
        return $this->meta_title ?: $this->title.' - szkolenie dla rad pedagogicznych';
    }

    public function seoDescription(): string
    {
        if ($this->meta_description) {
            return $this->meta_description;
        }

        if ($this->summary) {
            return $this->summary;
        }

        return 'Oferta szkolenia dla rad pedagogicznych przygotowana przez Platformę Nowoczesnej Edukacji.';
    }
}
