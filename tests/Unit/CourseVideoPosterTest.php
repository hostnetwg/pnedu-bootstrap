<?php

namespace Tests\Unit;

use App\Models\CourseVideo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CourseVideoPosterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }
    public function test_youtube_poster_url_uses_video_id(): void
    {
        $video = new CourseVideo([
            'platform' => 'youtube',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ]);

        $this->assertSame(
            'https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg',
            $video->getPosterUrl()
        );
    }

    public function test_vimeo_poster_url_uses_oembed_thumbnail(): void
    {
        Http::fake([
            'vimeo.com/api/oembed.json*' => Http::response([
                'thumbnail_url' => 'https://i.vimeocdn.com/video/1234567890.jpg',
            ], 200),
        ]);

        $video = new CourseVideo([
            'platform' => 'vimeo',
            'video_url' => 'https://vimeo.com/76979871',
        ]);

        $this->assertSame(
            'https://i.vimeocdn.com/video/1234567890.jpg',
            $video->getPosterUrl()
        );
    }

    public function test_vimeo_poster_url_returns_null_when_oembed_fails(): void
    {
        Http::fake([
            'vimeo.com/api/oembed.json*' => Http::response([], 404),
        ]);

        $video = new CourseVideo([
            'platform' => 'vimeo',
            'video_url' => 'https://vimeo.com/11111111',
        ]);

        $this->assertNull($video->getPosterUrl());
    }
}
