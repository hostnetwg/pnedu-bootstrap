<?php

return [

    'brand_suffix' => ' | PNE',

    'default_title_max' => 60,

    'default_description_max' => 160,

    /*
    |--------------------------------------------------------------------------
    | Metadane list bezpłatnych cykli (free.blade.php)
    |--------------------------------------------------------------------------
    */
    'listings' => [
        'courses.free' => [
            'title' => 'TIK w pracy nauczyciela – bezpłatne webinary | PNE',
            'description' => 'Bezpłatne webinary dla nauczycieli: TIK, narzędzia cyfrowe i praktyczne scenariusze do pracy w szkole.',
        ],
        'courses.office365' => [
            'title' => 'Szkolny administrator Office 365 – bezpłatne szkolenia | PNE',
            'description' => 'Bezpłatne szkolenia online o Microsoft Office 365 w szkole: administracja, bezpieczeństwo i codzienna praca zespołu.',
        ],
        'courses.parent-academy' => [
            'title' => 'Akademia Rodzica – bezpłatne webinary | PNE',
            'description' => 'Bezpłatne webinary dla rodziców i opiekunów: wsparcie edukacyjne, komunikacja ze szkołą i praktyczne wskazówki.',
        ],
        'courses.director-academy' => [
            'title' => 'Akademia Dyrektora – bezpłatne webinary | PNE',
            'description' => 'Bezpłatne webinary dla dyrektorów szkół i przedszkoli: prawo oświatowe, zarządzanie, dokumentacja i organizacja pracy placówki.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Ręczne SEO dla wybranych kursów (raport SEO 2026-08-23)
    |--------------------------------------------------------------------------
    */
    'course_overrides' => [
        540 => [
            'title' => 'Nowa podstawa programowa 2026 – szkolenie | PNE',
            'description' => 'Praktyczne szkolenie o podstawie programowej 2026 i ramowych planach nauczania. Zmiany, wdrożenie, materiały i nagranie.',
        ],
        548 => [
            'title' => 'Nowelizacja statutu szkoły 2026 – szkolenie | PNE',
            'description' => 'Sprawdź zmiany w statucie szkoły i przedszkola po reformach 2026. Gotowe zapisy statutowe, materiały, nagranie i certyfikat.',
        ],
    ],

];
