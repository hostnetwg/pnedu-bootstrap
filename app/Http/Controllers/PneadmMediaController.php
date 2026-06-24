<?php

namespace App\Http\Controllers;

use App\Support\PneadmMedia;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

/**
 * Proxy grafik z adm.pnedu.pl — ten sam origin co pnedu.pl (bez hotlinkingu / cache 404 w przeglądarce).
 */
class PneadmMediaController extends Controller
{
    public function show(string $path): Response
    {
        $normalized = ltrim($path, '/');
        if ($normalized === '' || ! PneadmMedia::isAllowedPath($normalized)) {
            abort(404);
        }

        $sourceUrl = rtrim((string) config('services.pneadm.public_url'), '/').'/storage/'.$normalized;

        $response = Http::timeout((int) config('services.pneadm.timeout', 30))
            ->withHeaders(['User-Agent' => 'pnedu-media-proxy/1.0'])
            ->get($sourceUrl);

        if (! $response->successful()) {
            abort($response->status() === 404 ? 404 : 502);
        }

        $contentType = $response->header('Content-Type') ?? 'application/octet-stream';

        return response($response->body(), 200, [
            'Content-Type' => $contentType,
            'Cache-Control' => 'public, max-age=604800, stale-while-revalidate=86400',
        ]);
    }
}
