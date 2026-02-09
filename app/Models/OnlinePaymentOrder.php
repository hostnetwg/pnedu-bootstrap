<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OnlinePaymentOrder extends Model
{
    use HasFactory;

    /**
     * Połączenie z bazą pneadm – administracja z adm.pnedu.pl
     */
    protected $connection = 'pneadm';

    protected $table = 'online_payment_orders';

    protected $fillable = [
        'ident',
        'course_id',
        'payment_gateway',
        'payu_order_id',
        'status',
        'total_amount',
        'currency',
        'buyer_type',
        'email',
        'first_name',
        'last_name',
        'phone',
        'order_comment',
        'address_data',
        'form_data',
        'ip_address',
    ];

    protected $casts = [
        'address_data' => 'array',
        'form_data' => 'array',
        'total_amount' => 'decimal:2',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_CREATED = 'created';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_FAILED = 'failed';

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function webhookLogs()
    {
        return $this->hasMany(WebhookLog::class, 'online_payment_order_id');
    }

    /**
     * Generuj identyfikator zamówienia w formacie PNEDU_{numer_kolejny}
     * Numer kolejny jest oparty na maksymalnym ID + 1, aby zapewnić sekwencyjność.
     */
    public static function generateIdent(): string
    {
        return DB::connection('pneadm')->transaction(function () {
            // Użyj lockForUpdate aby uniknąć kolizji przy równoczesnych zapytaniach
            $maxId = DB::connection('pneadm')
                ->table('online_payment_orders')
                ->lockForUpdate()
                ->max('id') ?? 0;
            
            $nextNumber = $maxId + 1;
            $ident = 'PNEDU_' . $nextNumber;
            
            // Sprawdź czy przypadkiem nie istnieje (na wypadek ręcznej edycji lub innych przypadków)
            // Jeśli istnieje, zwiększ numer aż znajdziemy wolny
            while (self::where('ident', $ident)->exists()) {
                $nextNumber++;
                $ident = 'PNEDU_' . $nextNumber;
            }
            
            return $ident;
        });
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }
}
