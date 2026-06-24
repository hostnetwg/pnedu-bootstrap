<?php

namespace App\Support;

class PneadmMedia
{
    /** Ścieżki względne w storage/app/public pneadm udostępniane na pnedu.pl. */
    public static function isAllowedPath(string $path): bool
    {
        return (bool) preg_match(
            '#^(courses/images|course_series|online-courses/images|instructors)/#',
            ltrim($path, '/')
        );
    }

    /**
     * URL miniatury / zdjęcia z publicznego storage pneadm (bezpośrednio lub przez proxy pnedu).
     */
    public static function url(?string $relativePath): ?string
    {
        $path = trim((string) $relativePath);
        if ($path === '') {
            return null;
        }

        $normalized = ltrim($path, '/');

        if (filter_var(config('services.pneadm.media_proxy'), FILTER_VALIDATE_BOOLEAN)) {
            return url('/media/pneadm/'.$normalized);
        }

        $base = rtrim((string) config('services.pneadm.public_url'), '/');

        return $base.'/storage/'.$normalized;
    }
}
