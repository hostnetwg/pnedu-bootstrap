<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Blokada indeksowania (np. staging, lokalne testy bez indeksu)
    |--------------------------------------------------------------------------
    |
    | true  → meta robots noindex,nofollow oraz robots.txt Disallow: /
    | false → indeksowanie dozwolone (sitemap + Allow: /)
    |
    | Domyślnie: false (indeksowanie włączone). Aby wyłączyć na dev/staging:
    | w .env ustaw SEO_BLOCK_INDEXING=true
    |
    */

    'block_search_indexing' => filter_var(
        env('SEO_BLOCK_INDEXING', '0'),
        FILTER_VALIDATE_BOOLEAN
    ),

    /*
    |--------------------------------------------------------------------------
    | Domyślny opis (meta description / Open Graph)
    |--------------------------------------------------------------------------
    */

    'default_description' => env(
        'SEO_DEFAULT_DESCRIPTION',
        'Szkolenia online dla nauczycieli, dyrektorów i szkół: kompetencje cyfrowe, AI w edukacji, Office 365, TIK, webinary i zaświadczenia. Akredytowany ośrodek doskonalenia – Platforma Nowoczesnej Edukacji (pnedu.pl).'
    ),

    /*
    |--------------------------------------------------------------------------
    | Obraz Open Graph (pełny URL, np. https://twoja-domena.pl/images/og-default.jpg)
    |--------------------------------------------------------------------------
    */

    'default_og_image' => env('SEO_OG_IMAGE') ?: (rtrim((string) env('APP_URL', 'http://localhost'), '/').'/logo-pne.png'),

];
