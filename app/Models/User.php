<?php

namespace App\Models;

use App\Models\Concerns\NormalizesUserEmail;
use App\Notifications\SystemResetPassword;
use App\Notifications\SystemVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, NormalizesUserEmail, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'birth_date',
        'birth_place',
        'email',
        'email_unique_slot',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'birth_date' => 'date',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's full name.
     */
    public function getNameAttribute(): string
    {
        $firstName = $this->attributes['first_name'] ?? '';
        $lastName = $this->attributes['last_name'] ?? '';

        return trim($firstName.' '.$lastName);
    }

    /**
     * Set the user's name (split into first_name and last_name).
     */
    public function setNameAttribute(string $value): void
    {
        $nameParts = explode(' ', trim($value), 2);
        $this->attributes['first_name'] = $nameParts[0] ?? null;
        $this->attributes['last_name'] = $nameParts[1] ?? null;
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new SystemVerifyEmail);
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new SystemResetPassword($token));
    }
}
