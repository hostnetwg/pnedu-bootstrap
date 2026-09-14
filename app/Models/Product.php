<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

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

    public function scopeOrderForPublicCatalog(Builder $query): Builder
    {
        $query->orderByRaw('CASE WHEN '.$this->salesOpenCatalogSql().' THEN 0 ELSE 1 END');

        if (Schema::connection($this->getConnectionName() ?: 'pneadm')->hasColumn('online_courses', 'catalog_sort_order')) {
            $query->orderByRaw('(
                SELECT catalog_sort_order
                FROM online_courses
                WHERE online_courses.id = products.resource_id
                  AND online_courses.deleted_at IS NULL
                LIMIT 1
            )');
        }

        return $query->orderBy('name')->orderBy('id');
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

    private function salesOpenCatalogSql(): string
    {
        $code = ProductOffer::DEFAULT_CODE;
        $channel = ProductOffer::CHANNEL_PNEDU;

        return <<<SQL
products.is_active = 1
AND EXISTS (
    SELECT 1
    FROM product_offers
    INNER JOIN product_prices
        ON product_prices.product_offer_id = product_offers.id
        AND product_prices.deleted_at IS NULL
        AND product_prices.is_active = 1
    WHERE product_offers.product_id = products.id
      AND product_offers.deleted_at IS NULL
      AND product_offers.code = '{$code}'
      AND product_offers.sales_channel = '{$channel}'
      AND product_offers.is_active = 1
      AND product_offers.is_public = 1
      AND (
            product_offers.allow_deferred_invoice = 1
            OR product_offers.allow_payu = 1
            OR product_offers.allow_paynow = 1
      )
)
SQL;
    }
}
