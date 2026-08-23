<?php

namespace App\Models;

use App\Support\PneadmMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Article extends Model
{
    public const STATUS_PUBLISHED = 'published';

    protected $connection = 'pneadm';

    protected $table = 'articles';

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content_html',
        'status',
        'published_at',
        'author_id',
        'cover_image',
        'meta_title',
        'meta_description',
        'comments_enabled',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'comments_enabled' => 'boolean',
        'view_count' => 'integer',
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereNull('deleted_at');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('sort_order')
            ->orderByDesc('published_at')
            ->orderByDesc('created_at');
    }

    public function publicImageUrl(): ?string
    {
        return PneadmMedia::url($this->cover_image);
    }

    protected function title(): Attribute
    {
        return Attribute::get(
            fn (?string $value) => $value === null ? null : static::normalizeNonBreakingSpaces($value)
        );
    }

    protected function excerpt(): Attribute
    {
        return Attribute::get(
            fn (?string $value) => $value === null ? null : static::normalizeNonBreakingSpaces($value)
        );
    }

    protected function metaTitle(): Attribute
    {
        return Attribute::get(
            fn (?string $value) => $value === null ? null : static::normalizeNonBreakingSpaces($value)
        );
    }

    protected function metaDescription(): Attribute
    {
        return Attribute::get(
            fn (?string $value) => $value === null ? null : static::normalizeNonBreakingSpaces($value)
        );
    }

    /**
     * &nbsp;, &#160; oraz podwójnie zakodowane &amp;nbsp; → znak twardej spacji (U+00A0).
     */
    public static function normalizeNonBreakingSpaces(string $value): string
    {
        $nbsp = html_entity_decode('&nbsp;', ENT_HTML5, 'UTF-8');

        for ($i = 0; $i < 2; $i++) {
            $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return str_replace(
            ['&nbsp;', '&#160;', '&#xA0;', '&#x00A0;'],
            $nbsp,
            $value
        );
    }

    public function plainText(?string $value = null): string
    {
        $plain = trim(strip_tags((string) ($value ?? '')));

        return static::normalizeNonBreakingSpaces($plain);
    }

    public function plainTitle(string $fallback = ''): string
    {
        $plain = $this->plainText($this->title);

        return $plain !== '' ? $plain : $fallback;
    }

    public function plainExcerpt(int $limit = 320): string
    {
        if (filled($this->excerpt)) {
            return $this->plainText($this->excerpt);
        }

        return Str::limit($this->plainText($this->content_html), $limit);
    }

    public function seoTitle(): string
    {
        return filled($this->meta_title)
            ? $this->plainText($this->meta_title)
            : $this->plainTitle();
    }

    public function seoDescription(): string
    {
        if (filled($this->meta_description)) {
            return $this->plainText($this->meta_description);
        }

        if (filled($this->excerpt)) {
            return $this->plainText($this->excerpt);
        }

        return Str::limit($this->plainText($this->content_html), 160);
    }

    public function readingTimeMinutes(): int
    {
        preg_match_all('/[\p{L}\p{N}\']+/u', $this->plainText($this->content_html), $matches);
        $words = count($matches[0] ?? []);

        return max(1, (int) ceil($words / 220));
    }

    /**
     * Zlicza jedno wyświetlenie artykułu na publicznym blogu (max. raz na sesję).
     */
    public function recordPublicView(): void
    {
        $sessionKey = 'blog_article_viewed_'.$this->id;

        if (session()->has($sessionKey)) {
            return;
        }

        session()->put($sessionKey, now()->timestamp);

        $this->increment('view_count');
    }

    public function formattedViewCount(): string
    {
        return number_format((int) $this->view_count, 0, ',', ' ');
    }

    public function viewCountLabel(): string
    {
        $count = (int) $this->view_count;
        $formatted = $this->formattedViewCount();

        $word = match (true) {
            $count === 1 => 'wyświetlenie',
            $count % 10 >= 2 && $count % 10 <= 4 && ($count % 100 < 12 || $count % 100 > 14) => 'wyświetlenia',
            default => 'wyświetleń',
        };

        return $formatted.' '.$word;
    }
}
