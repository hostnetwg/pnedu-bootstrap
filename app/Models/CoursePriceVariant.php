<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CoursePriceVariant extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'admpnedu';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'course_price_variants';

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
        'is_promotion' => 'boolean',
        'price' => 'decimal:2',
        'promotion_price' => 'decimal:2',
        'promotion_start' => 'datetime',
        'promotion_end' => 'datetime',
    ];

    /**
     * Get the course that owns this price variant.
     */
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    /**
     * Check if promotion is currently active.
     *
     * @return bool
     */
    public function isPromotionActive(): bool
    {
        // Jeśli promocja wyłączona
        if (!$this->is_promotion || $this->promotion_type === 'disabled') {
            return false;
        }

        // Jeśli promocja bez ram czasowych
        if ($this->promotion_type === 'unlimited') {
            return true;
        }

        // Jeśli promocja ograniczona czasowo
        if ($this->promotion_type === 'time_limited') {
            $now = now();
            if ($this->promotion_start && $this->promotion_end) {
                return $now >= $this->promotion_start && $now <= $this->promotion_end;
            }
        }

        return false;
    }

    /**
     * Get the current price (promotional or regular).
     *
     * @return float
     */
    public function getCurrentPrice(): float
    {
        if ($this->isPromotionActive() && $this->promotion_price !== null) {
            return (float) $this->promotion_price;
        }
        return (float) $this->price;
    }
}

