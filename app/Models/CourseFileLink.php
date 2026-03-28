<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseFileLink extends Model
{
    use HasFactory;

    protected $connection = 'pneadm';

    protected $table = 'course_file_links';

    protected $fillable = [
        'course_id',
        'url',
        'title',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Adres wskazuje na Dysk Google lub Dokumenty Google (ikonka „G” w UI).
     */
    public function isGoogleHostedUrl(): bool
    {
        $u = strtolower((string) $this->url);

        return str_contains($u, 'drive.google.com') || str_contains($u, 'docs.google.com');
    }
}
