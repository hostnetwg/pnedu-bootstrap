<?php

namespace App\Models;

use App\Casts\UtcImmutableDatetime;
use Illuminate\Database\Eloquent\Model;

class PriceOfferHistory extends Model
{
    public const SUBJECT_PRODUCT_PRICE = 'product_price';

    public const SUBJECT_COURSE_VARIANT = 'course_price_variant';

    public const KIND_REGULAR = 'regular';

    public const KIND_PROMOTIONAL = 'promotional';

    protected $connection = 'pneadm';

    protected $casts = [
        'offered_price' => 'decimal:2',
        'effective_from' => UtcImmutableDatetime::class,
        'effective_to' => UtcImmutableDatetime::class,
        'excluded_from_omnibus' => 'boolean',
        'excluded_at' => UtcImmutableDatetime::class,
    ];
}
