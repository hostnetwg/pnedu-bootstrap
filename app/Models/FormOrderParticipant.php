<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormOrderParticipant extends Model
{
    use HasFactory;

    protected $connection = 'pneadm';

    protected $table = 'form_order_participants';

    protected $fillable = [
        'form_order_id',
        'participant_firstname',
        'participant_lastname',
        'participant_email',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function formOrder()
    {
        return $this->belongsTo(FormOrder::class, 'form_order_id');
    }

    /**
     * Utwórz lub zaktualizuj uczestnika zamówienia w form_order_participants.
     */
    public static function syncFromFormOrder(FormOrder $formOrder, string $firstName, string $lastName, string $email): void
    {
        $participant = self::where('form_order_id', $formOrder->id)
            ->where('is_primary', true)
            ->whereNull('deleted_at')
            ->first();

        $data = [
            'participant_firstname' => self::normalizeNamePart($firstName),
            'participant_lastname' => self::normalizeNamePart($lastName),
            'participant_email' => trim($email),
            'is_primary' => true,
        ];

        if ($participant) {
            $participant->update($data);
        } else {
            self::create(array_merge($data, ['form_order_id' => $formOrder->id]));
        }
    }

    protected static function normalizeNamePart(string $part): string
    {
        $part = trim($part);
        if (empty($part)) {
            return $part;
        }
        return mb_convert_case(mb_strtolower($part), MB_CASE_TITLE, 'UTF-8');
    }
}
