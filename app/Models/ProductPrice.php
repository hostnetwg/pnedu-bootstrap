<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductPrice extends Model
{
    use SoftDeletes;

    public const TAX_EXEMPT = 'exempt';

    public const TAX_STANDARD = 'standard';

    public const ACCESS_UNLIMITED = 'unlimited';

    public const ACCESS_DURATION_FROM_GRANT = 'duration_from_grant';

    public const ACCESS_FIXED_UNTIL = 'fixed_until';

    protected $connection = 'pneadm';

    protected $fillable = [
        'product_offer_id',
        'name',
        'description',
        'is_active',
        'is_complimentary',
        'sort_order',
        'price',
        'currency',
        'tax_treatment',
        'tax_rate',
        'tax_exemption_basis',
        'is_promotion',
        'promotion_price',
        'promotion_starts_at',
        'promotion_ends_at',
        'show_promotion_countdown',
        'access_policy',
        'access_starts_at',
        'access_note',
        'access_duration_value',
        'access_duration_unit',
        'access_expires_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_complimentary' => 'boolean',
        'price' => 'decimal:2',
        'tax_rate' => 'decimal:4',
        'is_promotion' => 'boolean',
        'promotion_price' => 'decimal:2',
        'promotion_starts_at' => 'immutable_datetime',
        'promotion_ends_at' => 'immutable_datetime',
        'show_promotion_countdown' => 'boolean',
        'access_starts_at' => 'immutable_datetime',
        'access_duration_value' => 'integer',
        'access_expires_at' => 'immutable_datetime',
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(ProductOffer::class, 'product_offer_id');
    }

    public function isComplimentary(): bool
    {
        return (bool) $this->is_complimentary;
    }

    public function isPromotionActive(?CarbonInterface $at = null): bool
    {
        if ($this->isComplimentary() || ! $this->is_promotion || $this->promotion_price === null) {
            return false;
        }

        $moment = CarbonImmutable::instance($at ?? now());

        return ! ($this->promotion_starts_at && $moment->lt($this->promotion_starts_at))
            && ! ($this->promotion_ends_at && $moment->gt($this->promotion_ends_at));
    }

    public function currentPrice(?CarbonInterface $at = null): string
    {
        return (string) ($this->isPromotionActive($at) ? $this->promotion_price : $this->price);
    }

    public function omnibusLowestPrice(?CarbonInterface $at = null): ?string
    {
        return app(\App\Services\PriceOmnibusService::class)->lowestFor($this, $at);
    }

    public function promotionEndLabel(?CarbonInterface $at = null): ?string
    {
        if (! $this->isPromotionActive($at) || ! $this->promotion_ends_at) {
            return null;
        }

        return $this->promotion_ends_at->timezone('Europe/Warsaw')->format('d.m.Y H:i');
    }

    public function shouldShowPromotionCountdown(?CarbonInterface $at = null): bool
    {
        if (! $this->show_promotion_countdown || ! $this->isPromotionActive($at) || ! $this->promotion_ends_at) {
            return false;
        }

        return CarbonImmutable::instance($this->promotion_ends_at)->utc()
            ->gt(CarbonImmutable::instance($at ?? now('UTC'))->utc());
    }

    public function promotionCountdownTargetIso(): ?string
    {
        if (! $this->shouldShowPromotionCountdown()) {
            return null;
        }

        return $this->promotion_ends_at->utc()->toIso8601String();
    }

    public function accessLabel(): string
    {
        $start = $this->accessStartLabel();

        if ($this->access_policy === self::ACCESS_UNLIMITED) {
            return $start !== null
                ? 'Dostęp bezterminowy od '.$start
                : 'Dostęp bezterminowy';
        }

        if ($this->access_policy === self::ACCESS_FIXED_UNTIL) {
            $end = $this->access_expires_at
                ? $this->access_expires_at->timezone('Europe/Warsaw')->format('d.m.Y')
                : null;

            if ($start !== null && $end !== null) {
                return 'Dostęp od '.$start.' do '.$end;
            }

            return $end !== null ? 'Dostęp do '.$end : 'Dostęp do ustalonej daty';
        }

        $value = (int) $this->access_duration_value;
        $unit = match ($this->access_duration_unit) {
            'days' => $value === 1 ? 'dzień' : 'dni',
            'months' => $value === 1 ? 'miesiąc' : 'miesięcy',
            'years' => $value === 1 ? 'rok' : 'lat',
            default => '',
        };

        if ($start !== null) {
            return "Dostęp przez {$value} {$unit} od {$start}";
        }

        return "Dostęp przez {$value} {$unit}";
    }

    public function accessStartsInFuture(?CarbonInterface $at = null): bool
    {
        if (! $this->access_starts_at) {
            return false;
        }

        return CarbonImmutable::instance($this->access_starts_at)->utc()
            ->gt(CarbonImmutable::instance($at ?? now('UTC'))->utc());
    }

    public function accessStartLabel(): ?string
    {
        if (! $this->access_starts_at) {
            return null;
        }

        return $this->access_starts_at->timezone('Europe/Warsaw')->format('d.m.Y');
    }
}
