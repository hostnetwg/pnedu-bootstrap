<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class OnlinePaymentOrder extends Model
{
    use HasFactory;

    /**
     * Połączenie z bazą pneadm – administracja z adm.pnedu.pl
     */
    protected $connection = 'pneadm';

    protected $table = 'online_payment_orders';

    protected $fillable = [
        'form_order_id',
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

    public function formOrder(): BelongsTo
    {
        return $this->belongsTo(FormOrder::class, 'form_order_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function webhookLogs()
    {
        return $this->hasMany(WebhookLog::class, 'online_payment_order_id');
    }

    /**
     * Generuj identyfikator zamówienia (extOrderId). Numer z max(id)+1 w transakcji.
     *
     * ONLINE_PAYMENT_ORDER_IDENT_PREFIX (np. PNEdu#) → PNEdu#1, PNEdu#2
     * Bez prefiksu: PNEDU_1; z ONLINE_PAYMENT_ORDER_IDENT_SEGMENT=local → PNEDU_local_1
     */
    public static function generateIdent(): string
    {
        $segment = config('services.online_payment_order.ident_segment');
        $segment = is_string($segment) && $segment !== ''
            ? preg_replace('/[^A-Za-z0-9_-]/', '', $segment)
            : '';

        $customPrefix = config('services.online_payment_order.ident_prefix');
        $customPrefix = is_string($customPrefix) && $customPrefix !== ''
            ? preg_replace('/[^A-Za-z0-9#_-]/', '', $customPrefix)
            : '';

        if ($customPrefix !== '') {
            $prefix = $customPrefix.($segment !== '' ? $segment.'_' : '');
        } else {
            $prefix = 'PNEDU_'.($segment !== '' ? $segment.'_' : '');
        }

        return DB::connection('pneadm')->transaction(function () use ($prefix) {
            // Użyj lockForUpdate aby uniknąć kolizji przy równoczesnych zapytaniach
            $maxId = DB::connection('pneadm')
                ->table('online_payment_orders')
                ->lockForUpdate()
                ->max('id') ?? 0;

            $nextNumber = $maxId + 1;
            $ident = $prefix.$nextNumber;

            // Sprawdź czy przypadkiem nie istnieje (na wypadek ręcznej edycji lub innych przypadków)
            // Jeśli istnieje, zwiększ numer aż znajdziemy wolny
            while (self::where('ident', $ident)->exists()) {
                $nextNumber++;
                $ident = $prefix.$nextNumber;
            }

            return $ident;
        });
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PAID => 'Opłacone',
            self::STATUS_PENDING => 'Oczekuje',
            self::STATUS_CREATED => 'Utworzone',
            self::STATUS_CANCELLED => 'Anulowane',
            self::STATUS_FAILED => 'Nieudane',
            default => (string) ($this->status ?? 'Nieznany'),
        };
    }

    public function paymentGatewayLabel(): string
    {
        return match ($this->payment_gateway) {
            'payu' => 'PayU',
            'paynow' => 'PayNow.pl',
            default => (string) ($this->payment_gateway ?? 'Nieznana'),
        };
    }

    public function buyerTypeLabel(): string
    {
        return match ($this->buyer_type) {
            'person' => 'Osoba fizyczna',
            'company' => 'Firma',
            'organisation' => 'Instytucja',
            default => 'Nie określono',
        };
    }
}
