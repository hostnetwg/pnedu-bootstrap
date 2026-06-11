<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

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
        $raw = $this->normalizedVideoUrl();

        if ($this->platform === 'youtube') {
            $videoId = $this->extractYouTubeId($raw);
            if ($videoId) {
                return "https://www.youtube.com/embed/{$videoId}";
            }

            return $raw;
        }

        if ($this->platform === 'vimeo') {
            $videoId = $this->extractVimeoId($raw);
            if ($videoId) {
                return 'https://player.vimeo.com/video/'.$videoId.'?badge=0&autopause=0&player_id=0&app_id=58479&dnt=1&title=0&byline=0&portrait=0';
            }
        }

        return $raw;
    }

    /**
     * URL miniatury do podglądu przed odtworzeniem (YouTube: statyczny CDN; Vimeo: oEmbed + cache).
     */
    public function getPosterUrl(): ?string
    {
        $raw = $this->normalizedVideoUrl();

        if ($this->platform === 'youtube') {
            $videoId = $this->extractYouTubeId($raw);

            return $videoId ? 'https://i.ytimg.com/vi/'.$videoId.'/hqdefault.jpg' : null;
        }

        if ($this->platform === 'vimeo') {
            $videoId = $this->extractVimeoId($raw);
            if (! $videoId) {
                return null;
            }

            return Cache::remember(
                'course_video_vimeo_poster_'.$videoId,
                now()->addDays(7),
                function () use ($videoId): ?string {
                    try {
                        $response = Http::timeout(5)
                            ->acceptJson()
                            ->get('https://vimeo.com/api/oembed.json', [
                                'url' => 'https://vimeo.com/'.$videoId,
                            ]);

                        if ($response->successful()) {
                            $thumbnailUrl = $response->json('thumbnail_url');

                            return is_string($thumbnailUrl) && $thumbnailUrl !== '' ? $thumbnailUrl : null;
                        }
                    } catch (\Throwable) {
                        // Brak miniatury — facade pokaże neutralne tło z przyciskiem play.
                    }

                    return null;
                }
            );
        }

        return null;
    }

    private function normalizedVideoUrl(): string
    {
        $url = trim((string) $this->video_url);
        if ($url === '') {
            return '';
        }

        return html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function extractYouTubeId(string $url): ?string
    {
        if ($url === '') {
            return null;
        }

        $tryUrl = urldecode($url);
        foreach ([$url, $tryUrl] as $candidate) {
            $query = parse_url($candidate, PHP_URL_QUERY);
            if (is_string($query) && $query !== '') {
                parse_str($query, $params);
                if (! empty($params['v']) && preg_match('/^[a-zA-Z0-9_-]{11}$/', (string) $params['v'])) {
                    return (string) $params['v'];
                }
            }
        }

        $patterns = [
            '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/|youtube-nocookie\.com\/embed\/)([a-zA-Z0-9_-]{11})/',
            '/(?:m\.)?(?:youtube\.com|youtube-nocookie\.com)\/(?:embed|shorts|live)\/([a-zA-Z0-9_-]{11})/',
            '/youtube\.com\/watch\?[^#]*[&?]v=([a-zA-Z0-9_-]{11})/',
            '/(?:www\.)?youtu\.be\/([a-zA-Z0-9_-]{11})(?:[?&#]|$)/',
        ];

        foreach ([$url, $tryUrl] as $candidate) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $candidate, $matches)) {
                    return $matches[1];
                }
            }
        }

        return null;
    }

    private function extractVimeoId(string $url): ?string
    {
        $patterns = [
            '/vimeo\.com\/(\d+)/',
            '/player\.vimeo\.com\/video\/(\d+)/',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }
}
