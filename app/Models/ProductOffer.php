<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductOffer extends Model
{
    use SoftDeletes;

    public const DEFAULT_CODE = 'default';

    public const CHANNEL_PNEDU = 'pnedu';

    protected $connection = 'pneadm';

    protected $casts = [
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'allow_multiple_recipients' => 'boolean',
        'allow_deferred_invoice' => 'boolean',
        'allow_payu' => 'boolean',
        'allow_paynow' => 'boolean',
        'satisfaction_guarantee_days' => 'integer',
    ];

    public function satisfactionGuaranteeDays(): int
    {
        return max(0, (int) ($this->satisfaction_guarantee_days ?? 0));
    }

    public function hasSatisfactionGuarantee(): bool
    {
        return $this->satisfactionGuaranteeDays() > 0;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function activePrices(): HasMany
    {
        return $this->prices()->where('is_active', true);
    }
}
