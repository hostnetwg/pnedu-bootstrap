<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
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
    protected $table = 'certificates';

    protected $fillable = [
        'participant_id',
        'course_id',
        'certificate_number',
        'file_path',
        'issue_date',
        'generated_at'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'generated_at' => 'datetime',
    ];

    public function participant()
    {
        return $this->belongsTo(Participant::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}

