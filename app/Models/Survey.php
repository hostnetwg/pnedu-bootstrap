<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Survey extends Model
{
    use HasFactory;

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
    protected $table = 'surveys';

    protected $fillable = [
        'course_id',
        'instructor_id',
        'title',
        'description',
        'imported_at',
        'imported_by',
        'source',
        'total_responses',
        'original_file_path',
        'metadata'
    ];

    protected $casts = [
        'imported_at' => 'datetime',
        'metadata' => 'array'
    ];

    /**
     * Relacja do pytań ankiety
     */
    public function questions(): HasMany
    {
        return $this->hasMany(SurveyQuestion::class)->orderBy('question_order');
    }

    /**
     * Relacja do odpowiedzi
     */
    public function responses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }
}

