<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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
     * Pobierz lub utwórz token dla e-maila (jak w pneadm-bootstrap).
     */
    public static function getOrCreateTokenForEmail(?string $email): string
    {
        $normalized = self::normalizeEmail($email);
        if ($normalized === '') {
            return '';
        }

        $record = self::firstOrCreate(
            ['email_normalized' => $normalized],
            ['token' => Str::random(64)]
        );

        return $record->token;
    }

    /**
     * Znajdź rekord po tokenie. Zwraca model lub null.
     */
    public static function findByToken(string $token): ?self
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        return self::where('token', $token)->first();
    }
}
