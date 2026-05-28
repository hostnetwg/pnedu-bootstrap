<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CoursePriceVariant extends Model
{
    use HasFactory, SoftDeletes;

    public const AVAILABILITY_ALWAYS = 'always';

    public const AVAILABILITY_HIDE_AFTER_END = 'hide_after_end';

    public const AVAILABILITY_SHOW_AFTER_END = 'show_after_end';

    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'pneadm';

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
        'access_start_datetime' => 'datetime',
        'access_end_datetime' => 'datetime',
        'access_duration_value' => 'integer',
        'post_end_access_duration_value' => 'integer',
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

    public function isAvailableForCourseEndState(bool $courseEnded): bool
    {
        return match ($this->availability_after_course_end ?? self::AVAILABILITY_ALWAYS) {
            self::AVAILABILITY_HIDE_AFTER_END => ! $courseEnded,
            self::AVAILABILITY_SHOW_AFTER_END => $courseEnded,
            default => true,
        };
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

