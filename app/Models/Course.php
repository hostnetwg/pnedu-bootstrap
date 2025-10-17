<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Instructor;

class Course extends Model
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
    protected $table = 'courses';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'offer_description_html',
        'start_date',
        'end_date',
        'is_paid',
        'type',
        'category',
        'instructor_id',
        'image',
        'is_active',
        'certificate_format',
        'source_id_old',
    ];

    /**
     * Get the formatted date.
     *
     * @return string
     */
    public function getFormattedDateAttribute()
    {
        // Prioritize start_date since that's what exists in the database
        if (isset($this->attributes['start_date'])) {
            return date('d.m.Y H:i', strtotime($this->start_date));
        } elseif (isset($this->attributes['created_at'])) {
            return date('d.m.Y H:i', strtotime($this->created_at));
        }
        
        return 'Data niedostępna';
    }

    /**
     * Get the trainer name, trainer attribute or instructor ID.
     *
     * @return string
     */
    public function getTrainerAttribute(): string
    {
        if (!empty($this->attributes['trainer'])) {
            return $this->attributes['trainer'];
        }

        if ($this->instructor) {
            $name = $this->instructor->full_name;
            if (!empty($this->instructor->title)) {
                $name = $this->instructor->title . ' ' . $name;
            }
            return $name;
        }

        if (isset($this->attributes['instructor_id'])) {
            return (string) $this->attributes['instructor_id'];
        }

        return 'Brak trenera';
    }

    /**
     * Get the appropriate trainer title based on gender.
     *
     * @return string
     */
    public function getTrainerTitleAttribute(): string
    {
        if ($this->instructor) {
            return $this->instructor->gender_title;
        }
        
        // Fallback: try to determine gender from trainer name if it's a direct string
        if (!empty($this->attributes['trainer'])) {
            $trainerName = $this->attributes['trainer'];
            // Simple heuristic: if name ends with 'a' it might be female
            if (preg_match('/\b\w*a\b$/', $trainerName)) {
                return 'Prowadząca';
            }
            return 'Prowadzący';
        }
        
        return 'Trener';
    }

    /**
     * Course belongs to an instructor.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class, 'instructor_id');
    }
}