<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseVideo extends Model
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
    protected $table = 'course_videos';

    protected $fillable = [
        'course_id',
        'video_url',
        'platform',
        'title',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    /**
     * Relacja do kursu
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Zwraca URL do odtworzenia wideo (do otwarcia w przeglądarce).
     */
    public function getWatchUrl(): string
    {
        return $this->video_url;
    }

    /**
     * Pobiera pełny URL do embedowania wideo (iframe).
     */
    public function getEmbedUrl(): string
    {
        if ($this->platform === 'youtube') {
            $videoId = $this->extractYouTubeId($this->video_url);
            return $videoId ? "https://www.youtube.com/embed/{$videoId}" : $this->video_url;
        }
        if ($this->platform === 'vimeo') {
            $videoId = $this->extractVimeoId($this->video_url);
            if ($videoId) {
                return "https://player.vimeo.com/video/{$videoId}?badge=0&autopause=0&player_id=0&app_id=58479";
            }
        }

        return $this->video_url;
    }

    private function extractYouTubeId(string $url): ?string
    {
        $patterns = [
            '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/',
            '/youtube\.com\/watch\?.*v=([a-zA-Z0-9_-]{11})/',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    private function extractVimeoId(string $url): ?string
    {
        if (preg_match('/vimeo\.com\/(\d+)/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
