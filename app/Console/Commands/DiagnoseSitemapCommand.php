<?php

namespace App\Console\Commands;

use App\Services\Seo\SitemapUrlBuilder;
use Illuminate\Console\Command;
use Throwable;

class DiagnoseSitemapCommand extends Command
{
    protected $signature = 'seo:sitemap-diagnose';

    protected $description = 'Diagnostyka generowania /sitemap.xml (prod smoke).';

    public function handle(): int
    {
        $this->line('APP_URL='.config('app.url'));
        $this->line('SEO_BLOCK_INDEXING='.(config('seo.block_search_indexing') ? 'true' : 'false'));
        $this->line('SitemapUrlBuilder class exists: '.(class_exists(SitemapUrlBuilder::class) ? 'yes' : 'no'));

        try {
            $urls = app(SitemapUrlBuilder::class)->build();
            $this->info('URLs built: '.count($urls));
            $this->line(substr(SitemapUrlBuilder::renderXml($urls), 0, 400));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception::class.': '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
