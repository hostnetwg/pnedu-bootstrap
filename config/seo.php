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

    /*
    |--------------------------------------------------------------------------
    | Dane encji marki / organizacji
    |--------------------------------------------------------------------------
    */

    'organization' => [
        'legal_name' => env('SEO_ORG_LEGAL_NAME', 'Niepubliczny Ośrodek Doskonalenia Nauczycieli „Platforma Nowoczesnej Edukacji”'),
        'logo' => env('SEO_ORG_LOGO') ?: (rtrim((string) env('APP_URL', 'http://localhost'), '/').'/logo-pne.png'),
        'email' => env('SEO_ORG_EMAIL', 'kontakt@pnedu.pl'),
        'telephone' => env('SEO_ORG_PHONE', '+48-501-654-274'),
        'address' => [
            'street' => env('SEO_ORG_ADDRESS_STREET', 'ul. A. Zamoyskiego 30/14'),
            'locality' => env('SEO_ORG_ADDRESS_LOCALITY', 'Bieżuń'),
            'postal_code' => env('SEO_ORG_ADDRESS_POSTAL_CODE', '09-320'),
            'country' => env('SEO_ORG_ADDRESS_COUNTRY', 'PL'),
        ],
        'same_as' => [
            'https://www.facebook.com/WaldemarGrabowskiEdukacja/',
            'https://www.instagram.com/platforma.nowoczesnej.edukacji/',
            'https://www.youtube.com/c/WaldemarGrabowskiEdukacja',
            'https://www.linkedin.com/in/waldemar-grabowski/',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Strona główna — krótki cache HTML dla gości (CDN / LiteSpeed)
    |--------------------------------------------------------------------------
    |
    | 0 = wyłączony. Domyślnie 60 s + stale-while-revalidate 120 s.
    |
    */

    'homepage' => [
        'page_cache_max_age' => (int) env('HOMEPAGE_PAGE_CACHE_MAX_AGE', 60),
        'page_cache_stale_while_revalidate' => (int) env('HOMEPAGE_PAGE_CACHE_STALE', 120),
    ],

];
