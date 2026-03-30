<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Blokada indeksowania (środowiska dev / staging)
    |--------------------------------------------------------------------------
    |
    | true  → meta robots noindex,nofollow oraz robots.txt Disallow: /
    | false → strona dostępna dla wyszukiwarek (sitemap + Allow: /)
    |
    | Domyślnie: blokada poza production. Na produkcji ustaw APP_ENV=production
    | lub jawnie SEO_BLOCK_INDEXING=false.
    |
    */

    'block_search_indexing' => filter_var(
        env('SEO_BLOCK_INDEXING'),
        FILTER_VALIDATE_BOOLEAN,
        FILTER_NULL_ON_FAILURE
    ) ?? (env('APP_ENV', 'production') !== 'production'),

    /*
    |--------------------------------------------------------------------------
    | Domyślny opis (meta description / Open Graph)
    |--------------------------------------------------------------------------
    */

    'default_description' => env(
        'SEO_DEFAULT_DESCRIPTION',
        'Szkolenia online dla nauczycieli, dyrektorów i szkół: kompetencje cyfrowe, AI w edukacji, Office 365, TIK i certyfikaty. Platforma Nowoczesnej Edukacji.'
    ),

    /*
    |--------------------------------------------------------------------------
    | Obraz Open Graph (pełny URL, np. https://twoja-domena.pl/images/og-default.jpg)
    |--------------------------------------------------------------------------
    */

    'default_og_image' => env('SEO_OG_IMAGE'),

];
