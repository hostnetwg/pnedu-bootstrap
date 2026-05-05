<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Course extends Model
{
    use HasFactory;

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
    protected $table = 'courses';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'offer_description_html',
        'start_date',
        'end_date',
        'is_paid',
        'type',
        'category',
        'instructor_id',
        'image',
        'is_active',
        'certificate_format',
        'id_old',
        'source_id_old',
        'show_on_pnedu',
        'publigo_product_id',
        'publigo_price_id',
        'sendy_suppression_list_id',
    ];

    /**
     * Get the formatted date.
     *
     * @return string
     */
    public function getFormattedDateAttribute()
    {
        // Prioritize start_date since that's what exists in the database
        if (isset($this->attributes['start_date'])) {
            return date('d.m.Y H:i', strtotime($this->start_date));
        } elseif (isset($this->attributes['created_at'])) {
            return date('d.m.Y H:i', strtotime($this->created_at));
        }

        return 'Data niedostępna';
    }

    /**
     * Get the trainer name, trainer attribute or instructor ID.
     */
    public function getTrainerAttribute(): string
    {
        if (! empty($this->attributes['trainer'])) {
            return $this->attributes['trainer'];
        }

        if ($this->instructor) {
            $name = $this->instructor->full_name;
            if (! empty($this->instructor->title)) {
                $name = $this->instructor->title.' '.$name;
            }

            return $name;
        }

        if (isset($this->attributes['instructor_id'])) {
            return (string) $this->attributes['instructor_id'];
        }

        return 'Brak trenera';
    }

    /**
     * Get the appropriate trainer title based on gender.
     */
    public function getTrainerTitleAttribute(): string
    {
        if ($this->instructor) {
            return $this->instructor->gender_title;
        }

        // Fallback: try to determine gender from trainer name if it's a direct string
        if (! empty($this->attributes['trainer'])) {
            $trainerName = $this->attributes['trainer'];
            // Simple heuristic: if name ends with 'a' it might be female
            if (preg_match('/\b\w*a\b$/', $trainerName)) {
                return 'Prowadząca';
            }

            return 'Prowadzący';
        }

        return 'Trener';
    }

    /**
     * Course belongs to an instructor.
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class, 'instructor_id');
    }

    /**
     * Course has many price variants.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function priceVariants()
    {
        return $this->hasMany(CoursePriceVariant::class, 'course_id');
    }

    /**
     * Publiczny URL zapisu z listy (start): płatne → formularz zamówienia z domyślnym wariantem (najniższe aktywne ID, jak na stronie kursu);
     * bezpłatne → szczegóły (formularz e-mailowy Sendy).
     */
    public function publicOrderFormUrl(): string
    {
        if (! $this->is_paid) {
            return route('courses.show', $this->id);
        }

        $variants = $this->relationLoaded('priceVariants')
            ? $this->priceVariants
            : $this->priceVariants()->where('is_active', true)->orderBy('id')->get();

        $sorted = $variants->filter(fn ($v) => (bool) $v->is_active)
            ->sortBy(fn ($v) => (int) $v->id)
            ->values();

        $base = route('payment.order-form', $this->id);
        $first = $sorted->first();
        if ($first === null) {
            return $base;
        }

        return $base.'?price_variant_id='.$first->id;
    }

    /**
     * Course has one online detail.
     */
    public function onlineDetail(): HasOne
    {
        return $this->hasOne(CourseOnlineDetail::class, 'course_id');
    }

    /**
     * Course has many certificates.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function certificates()
    {
        return $this->hasMany(Certificate::class, 'course_id');
    }

    /**
     * Course has many videos (nagrania wideo).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function videos()
    {
        return $this->hasMany(CourseVideo::class)->orderBy('order');
    }

    /**
     * Linki do materiałów (np. Dysk Google).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fileLinks()
    {
        return $this->hasMany(CourseFileLink::class)->orderBy('order');
    }

    /**
     * Get the current price for the course, considering promotions.
     *
     * @return array|null Returns ['price' => float, 'original_price' => float|null, 'is_promotion' => bool, 'promotion_end' => string|null, 'promotion_type' => string|null, 'price_variant_id' => int, 'variant_name' => string|null] or null if no price found
     */
    public function getCurrentPrice(): ?array
    {
        // Try to find price variant by course_id first
        $priceVariant = CoursePriceVariant::where('course_id', $this->id)
            ->where('is_active', 1)
            ->orderBy('price', 'asc') // Get cheapest variant
            ->first();

        // If not found and we have publigo_price_id, try to find by it
        if (! $priceVariant && $this->publigo_price_id) {
            $priceVariant = CoursePriceVariant::where('id', $this->publigo_price_id)
                ->where('is_active', 1)
                ->first();
        }

        if (! $priceVariant) {
            return null;
        }

        return $this->priceInfoFromVariant($priceVariant);
    }

    /**
     * Cena i promocja do nagłówka formularza zamówienia — dla konkretnego wariantu (np. z ?price_variant_id= lub ukrytego pola).
     * Przy braku wariantu lub nieznanym ID: zachowanie jak {@see getCurrentPrice()}.
     *
     * @return array|null Ten sam kształt co {@see getCurrentPrice()}
     */
    public function getPriceInfoForOrderFormHeader(?int $priceVariantId): ?array
    {
        if ($priceVariantId === null || $priceVariantId === 0) {
            return $this->getCurrentPrice();
        }

        $priceVariant = CoursePriceVariant::where('course_id', $this->id)
            ->where('id', $priceVariantId)
            ->first();

        if (! $priceVariant) {
            return $this->getCurrentPrice();
        }

        return $this->priceInfoFromVariant($priceVariant);
    }

    /**
     * @return array{price: float, original_price: float|null, is_promotion: bool, promotion_end: mixed, promotion_type: mixed, price_variant_id: int, variant_name: string|null}
     */
    protected function priceInfoFromVariant(CoursePriceVariant $priceVariant): array
    {
        $isPromotionActive = $priceVariant->isPromotionActive();
        $currentPrice = $priceVariant->getCurrentPrice();
        $originalPrice = $isPromotionActive ? (float) $priceVariant->price : null;

        $promotionEndDate = null;
        if ($isPromotionActive && $priceVariant->promotion_type === 'time_limited' && $priceVariant->promotion_end) {
            $promotionEndDate = $priceVariant->promotion_end;
        }

        $name = $priceVariant->name;

        return [
            'price' => round($currentPrice, 2),
            'original_price' => $originalPrice ? round($originalPrice, 2) : null,
            'is_promotion' => $isPromotionActive,
            'promotion_end' => $promotionEndDate,
            'promotion_type' => $priceVariant->promotion_type,
            'price_variant_id' => (int) $priceVariant->id,
            'variant_name' => (is_string($name) && $name !== '') ? $name : null,
        ];
    }

    /**
     * Get the Publigo payment URL for this course.
     * Returns URL to Publigo cart if course is from Publigo, null otherwise.
     *
     * @param  int|null  $priceId  The price variant ID from Publigo (default: 1)
     */
    public function getPubligoPaymentUrl(?int $priceId = 1): ?string
    {
        // Check if course is from Publigo and has id_old
        if ($this->source_id_old === 'certgen_Publigo' && ! empty($this->id_old)) {
            return sprintf(
                'https://nowoczesna-edukacja.pl/zamowienie/?add-to-cart=%d&price-id=%d',
                $this->id_old,
                $priceId ?? 1
            );
        }

        return null;
    }
}
