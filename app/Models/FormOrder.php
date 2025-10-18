<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FormOrder extends Model
{
    use HasFactory;

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
    protected $table = 'form_orders';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ident',
        'ptw',
        'order_date',
        'product_id',
        'product_name',
        'product_price',
        'product_description',
        'publigo_product_id',
        'publigo_price_id',
        'publigo_sent',
        'publigo_sent_at',
        'participant_name',
        'participant_email',
        'orderer_name',
        'orderer_address',
        'orderer_postal_code',
        'orderer_city',
        'orderer_phone',
        'orderer_email',
        'buyer_name',
        'buyer_address',
        'buyer_postal_code',
        'buyer_city',
        'buyer_nip',
        'recipient_name',
        'recipient_address',
        'recipient_postal_code',
        'recipient_city',
        'recipient_nip',
        'invoice_number',
        'invoice_notes',
        'invoice_payment_delay',
        'status_completed',
        'notes',
        'updated_manually_at',
        'ip_address',
        'fb_source',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'order_date' => 'datetime',
        'publigo_sent_at' => 'datetime',
        'updated_manually_at' => 'datetime',
        'product_price' => 'decimal:2',
        'publigo_sent' => 'boolean',
        'status_completed' => 'boolean',
    ];

    /**
     * Generate a unique order identifier.
     *
     * @return string
     */
    public static function generateIdent(): string
    {
        do {
            // Generate format: YYMMDD-XXXXXX (6 random chars)
            $ident = date('ymd') . '-' . strtoupper(Str::random(6));
        } while (self::where('ident', $ident)->exists());

        return $ident;
    }

    /**
     * Relationship to Course.
     */
    public function course()
    {
        return $this->belongsTo(Course::class, 'product_id', 'id');
    }
}
