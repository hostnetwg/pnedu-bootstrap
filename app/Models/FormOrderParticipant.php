<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FormOrderParticipant extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'pneadm';

    protected $table = 'form_order_participants';

    protected $fillable = [
        'form_order_id',
        'participant_firstname',
        'participant_lastname',
        'participant_email',
        'is_primary',
        'participant_id',
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
     * Utwórz lub zaktualizuj głównego uczestnika zamówienia (kompatybilność wsteczna).
     */
    public static function syncFromFormOrder(FormOrder $formOrder, string $firstName, string $lastName, string $email): void
    {
        self::syncManyFromFormOrder($formOrder, [[
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
        ]]);
    }

    /**
     * Zsynchronizuj listę uczestników zamówienia. Pierwszy wiersz = is_primary.
     * Dopasowanie po e-mailu zachowuje powiązanie participant_id.
     *
     * @param  list<array{first_name: string, last_name: string, email: string}>  $rows
     */
    public static function syncManyFromFormOrder(FormOrder $formOrder, array $rows): void
    {
        $normalized = [];
        foreach ($rows as $row) {
            $email = strtolower(trim((string) ($row['email'] ?? '')));
            $first = self::normalizeNamePart((string) ($row['first_name'] ?? ''));
            $last = self::normalizeNamePart((string) ($row['last_name'] ?? ''));
            if ($email === '' && $first === '' && $last === '') {
                continue;
            }
            $normalized[] = [
                'first_name' => $first,
                'last_name' => $last,
                'email' => $email,
            ];
        }

        if ($normalized === []) {
            return;
        }

        $existing = self::query()
            ->where('form_order_id', $formOrder->id)
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get();

        $byEmail = [];
        foreach ($existing as $participant) {
            $key = strtolower(trim((string) $participant->participant_email));
            if ($key !== '' && ! isset($byEmail[$key])) {
                $byEmail[$key] = $participant;
            }
        }

        $keepIds = [];
        foreach ($normalized as $index => $row) {
            $data = [
                'participant_firstname' => $row['first_name'],
                'participant_lastname' => $row['last_name'],
                'participant_email' => $row['email'],
                'is_primary' => $index === 0,
            ];

            $match = $row['email'] !== '' ? ($byEmail[$row['email']] ?? null) : null;
            if ($match && ! in_array($match->id, $keepIds, true)) {
                $match->update($data);
                $keepIds[] = $match->id;
                unset($byEmail[$row['email']]);

                continue;
            }

            $created = self::create(array_merge($data, [
                'form_order_id' => $formOrder->id,
            ]));
            $keepIds[] = $created->id;
        }

        foreach ($existing as $participant) {
            if (! in_array($participant->id, $keepIds, true)) {
                $participant->delete();
            }
        }
    }

    protected static function normalizeNamePart(string $part): string
    {
        $part = trim($part);
        if ($part === '') {
            return $part;
        }

        return mb_convert_case(mb_strtolower($part), MB_CASE_TITLE, 'UTF-8');
    }
}
