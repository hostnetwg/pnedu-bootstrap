<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    public const TYPE_ONLINE_COURSE = 'online_course';

    public const FULFILLMENT_ONLINE_COURSE_ACCESS = 'online_course_access';

    protected $connection = 'pneadm';

    protected $fillable = [
        'type',
        'resource_id',
        'name',
        'slug',
        'sku',
        'fulfillment_type',
        'is_active',
        'requires_shipping',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'resource_id' => 'integer',
        'is_active' => 'boolean',
        'requires_shipping' => 'boolean',
    ];

    public function offers(): HasMany
    {
        return $this->hasMany(ProductOffer::class);
    }

    public function defaultOffer(): HasOne
    {
        return $this->hasOne(ProductOffer::class)
            ->where('code', ProductOffer::DEFAULT_CODE)
            ->where('sales_channel', ProductOffer::CHANNEL_PNEDU);
    }

    public function onlineCourse(): BelongsTo
    {
        return $this->belongsTo(OnlineCourse::class, 'resource_id');
    }

    public function scopeCatalogOnlineCourses(Builder $query): Builder
    {
        return $query
            ->where('type', self::TYPE_ONLINE_COURSE)
            ->where('is_active', true)
            ->where('requires_shipping', false)
            ->whereHas('defaultOffer', fn (Builder $offer) => $offer->where('is_public', true))
            ->whereHas('onlineCourse', fn (Builder $course) => $course->where('is_active', true));
    }

    public function scopePurchasableOnlineCourses(Builder $query): Builder
    {
        return $query
            ->catalogOnlineCourses()
            ->whereHas('defaultOffer', fn (Builder $offer) => $offer
                ->where('is_active', true)
                ->where(fn (Builder $payment) => $payment
                    ->where('allow_deferred_invoice', true)
                    ->orWhere('allow_payu', true)
                    ->orWhere('allow_paynow', true)));
    }

    public function scopePublicOnlineCourses(Builder $query): Builder
    {
        return $query->catalogOnlineCourses();
    }

    public function isSalesOpen(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $offer = $this->defaultOffer;
        if (! $offer || ! $offer->is_active || ! $offer->is_public) {
            return false;
        }

        return $offer->allow_deferred_invoice || $offer->allow_payu || $offer->allow_paynow;
    }
}
