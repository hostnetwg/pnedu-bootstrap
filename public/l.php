<?php

/**
 * Wejście awaryjne dla krótkich linków /l/{campaign_code} (Apache + .htaccess).
 * Działa także gdy route:cache na produkcji nie zawiera jeszcze trasy Laravel.
 */
define('LARAVEL_START', microtime(true));

require __DIR__.'/../vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$code = $_GET['code'] ?? '';
$code = is_string($code) ? trim($code) : '';

if ($code === '' || ! preg_match('/^[A-Za-z0-9._-]+$/', $code)) {
    http_response_code(404);
    echo 'Not Found';
    exit;
}

$path = $app->make(App\Services\MarketingCampaignLinkResolver::class)->resolveRedirectPath($code);

if ($path === null) {
    http_response_code(404);
    echo 'Not Found';
    exit;
}

header('Location: '.$path, true, 302);
exit;
