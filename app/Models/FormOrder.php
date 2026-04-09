<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Zamówienia z formularza (baza pneadm, tabela form_orders).
 *
 * @property \Carbon\Carbon|null $pnedu_provisioned_at Zobacz komentarz kolumny w DB: data przyznania dostępu PNEDU.
 * @property bool|null $pnedu_user_existed_before Zobacz komentarz kolumny w DB: czy konto pnedu.users istniało wcześniej.
 */
class FormOrder extends Model
{
    use HasFactory, SoftDeletes;

    /** Faktura z odroczonym terminem (formularz „Wyślij zamówienie”). */
    public const PAYMENT_MODE_DEFERRED_INVOICE = 'deferred_invoice';

    /** Natychmiastowa płatność przez bramkę (formularz „Przejdź do płatności online”). */
    public const PAYMENT_MODE_ONLINE_GATEWAY = 'online_gateway';

    /** Zamówienie złożone (tryb odroczony). */
    public const PAYMENT_STATUS_SUBMITTED = 'submitted';

    /** Oczekiwanie na zaksięgowanie wpłaty w bramce. */
    public const PAYMENT_STATUS_AWAITING_PAYMENT = 'awaiting_payment';

    public const PAYMENT_STATUS_PAID = 'paid';

    public const PAYMENT_STATUS_CANCELLED = 'cancelled';

    public const PAYMENT_STATUS_FAILED = 'failed';

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
        'course_price_variant_id',
        'publigo_sent',
        'publigo_sent_at',
        'pnedu_provisioned_at',
        'pnedu_user_existed_before',
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
        'payment_mode',
        'payment_status',
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
        'pnedu_provisioned_at' => 'datetime',
        'pnedu_user_existed_before' => 'boolean',
        'updated_manually_at' => 'datetime',
        'product_price' => 'decimal:2',
        'course_price_variant_id' => 'integer',
        'publigo_sent' => 'boolean',
        'status_completed' => 'boolean',
    ];

    /**
     * Generate a unique order identifier.
     */
    public static function generateIdent(): string
    {
        do {
            // Generate format: YYMMDD-XXXXXX (6 random chars)
            $ident = date('ymd').'-'.strtoupper(Str::random(6));
        } while (self::withTrashed()->where('ident', $ident)->exists());

        return $ident;
    }

    /**
     * Relationship to Course.
     */
    public function course()
    {
        return $this->belongsTo(Course::class, 'product_id', 'id');
    }

    /**
     * Uczestnicy zamówienia (źródło prawdy: form_order_participants).
     */
    public function participants()
    {
        return $this->hasMany(FormOrderParticipant::class, 'form_order_id');
    }

    /**
     * Główny uczestnik (is_primary).
     */
    public function primaryParticipant()
    {
        return $this->hasOne(FormOrderParticipant::class, 'form_order_id')
            ->where('is_primary', true)
            ->whereNull('deleted_at');
    }

    /**
     * Zamówienie zablokowane do edycji przez klienta (zakończone lub z numerem faktury).
     */
    public function isEditLocked(): bool
    {
        if ($this->status_completed) {
            return true;
        }

        return trim((string) ($this->invoice_number ?? '')) !== '';
    }

    /**
     * Imię i nazwisko uczestnika (główny wiersz w form_order_participants).
     */
    public function getDisplayParticipantNameAttribute(): string
    {
        $p = $this->primaryParticipant;
        if ($p && trim(($p->participant_firstname ?? '').' '.($p->participant_lastname ?? '')) !== '') {
            return trim($p->participant_firstname.' '.$p->participant_lastname);
        }

        return '';
    }

    /**
     * E-mail uczestnika (główny wiersz w form_order_participants).
     */
    public function getDisplayParticipantEmailAttribute(): ?string
    {
        $p = $this->primaryParticipant;
        if ($p && ! empty(trim((string) ($p->participant_email ?? '')))) {
            return trim((string) $p->participant_email);
        }

        return null;
    }

    public static function paymentModeLabel(?string $mode, ?string $onlinePaymentGateway = null): string
    {
        return match ($mode) {
            self::PAYMENT_MODE_ONLINE_GATEWAY => match (strtolower((string) $onlinePaymentGateway)) {
                'payu' => 'Płatność online (bramka: PayU)',
                'paynow' => 'Płatność online (bramka: Paynow)',
                default => 'Płatność online (bramka)',
            },
            self::PAYMENT_MODE_DEFERRED_INVOICE => 'Faktura z odroczonym terminem',
            default => $mode ? (string) $mode : '—',
        };
    }

    public static function paymentStatusLabel(?string $status): string
    {
        return match ($status) {
            self::PAYMENT_STATUS_SUBMITTED => 'Złożone (odroczona)',
            self::PAYMENT_STATUS_AWAITING_PAYMENT => 'Oczekuje na płatność',
            self::PAYMENT_STATUS_PAID => 'Opłacone',
            self::PAYMENT_STATUS_CANCELLED => 'Anulowane',
            self::PAYMENT_STATUS_FAILED => 'Błąd płatności',
            default => $status ? (string) $status : '—',
        };
    }
}
