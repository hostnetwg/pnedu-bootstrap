<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Participant extends Model
{
    use HasFactory, SoftDeletes;

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
    protected $table = 'participants';

    protected $fillable = [
        'course_id',
        'order',        
        'first_name',
        'last_name',
        'email',
        'birth_date',
        'birth_place',
        'access_expires_at'
    ];

    protected $casts = [
        'birth_date' => 'date',
        'access_expires_at' => 'datetime',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function certificate()
    {
        return $this->hasOne(Certificate::class, 'participant_id');
    }

    /**
     * Sprawdź czy dostęp wygasł
     */
    public function hasExpiredAccess(): bool
    {
        if (!$this->access_expires_at) {
            return false; // Bezterminowy dostęp
        }
        
        // Porównujemy w UTC - czas w bazie jest zawsze w UTC
        $now = Carbon::now('UTC');
        $expiresAt = $this->access_expires_at->setTimezone('UTC');
        
        return $expiresAt->lt($now); // lt = less than (mniejsze niż)
    }

    /**
     * Sprawdź czy dostęp jest aktywny
     */
    public function hasActiveAccess(): bool
    {
        return !$this->hasExpiredAccess();
    }
}

