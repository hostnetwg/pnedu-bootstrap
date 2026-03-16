<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Mapowanie znormalizowany e-mail → unikalny token do linków pobierania zaświadczeń.
 * Odczyt z bazy pneadm (tabela participant_download_tokens).
 */
class ParticipantDownloadToken extends Model
{
    protected $connection = 'pneadm';

    protected $fillable = [
        'email_normalized',
        'token',
    ];

    /**
     * Normalizacja e-maila (trim + lowercase).
     */
    public static function normalizeEmail(?string $email): string
    {
        if ($email === null || $email === '') {
            return '';
        }
        return strtolower(trim($email));
    }

    /**
     * Znajdź rekord po tokenie. Zwraca model lub null.
     */
    public static function findByToken(string $token): ?self
    {
        return self::where('token', $token)->first();
    }
}
