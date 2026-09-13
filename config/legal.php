<?php

return [
    'terms' => [
        'current_version' => '2026-09-08',
        'draft_version' => 'draft-pending-approval',
        'versions' => [
            '2026-09-08' => [
                'effective_at' => '2026-09-08',
                'view' => 'legal.terms.versions.2026-09-08',
                'published' => true,
            ],
            'draft-pending-approval' => [
                'effective_at' => 'do ustalenia przy zatwierdzeniu',
                'view' => 'legal.terms.drafts.pending-approval',
                'published' => false,
            ],
        ],
    ],

    /*
     * Kwalifikacja kursu nagranego: pending | digital_content | digital_service | mixed.
     * pending = nie przełączamy zakresu; checkout kursów używa product_early_performance.
     */
    'product_qualification' => env('LEGAL_PRODUCT_QUALIFICATION', 'pending'),

    /*
     * false = bez zgody nie da się kupić, gdy start wpada w 14 dni (obecny proces).
     * true = zakup bez zgody, start po upływie terminu — wymaga osobnej akceptacji procesu.
     */
    'allow_purchase_without_early_consent' => (bool) env('LEGAL_ALLOW_PURCHASE_WITHOUT_EARLY_CONSENT', false),

    'early_performance' => [
        'window_days' => 14,
        'statement_version' => '2026-09-08-v2',
        'scope' => 'service_and_digital',
        'statement' => 'Proszę o rozpoczęcie realizacji szkolenia przed upływem 14 dni od zawarcia umowy i zgadzam się na udostępnienie w tym czasie nagrania oraz materiałów cyfrowych. Przyjmuję do wiadomości, że po pełnym wykonaniu usługi oraz dostarczeniu treści cyfrowych utracę prawo odstąpienia w odpowiednim zakresie.',
    ],

    /*
     * Oświadczenie na checkoutcie kursów nagranych. Zakres nadal service_and_digital.
     * Szkolenia live korzystają z early_performance powyżej.
     */
    'product_early_performance' => [
        'statement_version' => '2026-09-13-course-v1',
        'scope' => 'service_and_digital',
        'statement' => 'Proszę o rozpoczęcie kursu i zgadzam się na dostarczanie treści cyfrowych przed upływem 14 dni od zawarcia umowy. Przyjmuję do wiadomości utratę prawa odstąpienia: w zakresie usługi — po jej pełnym wykonaniu, a treści cyfrowych — po rozpoczęciu ich dostarczania.',
    ],

    'statements' => [
        'service_and_digital' => [
            'version' => '2026-09-08-v2',
            'statement' => 'Proszę o rozpoczęcie realizacji szkolenia przed upływem 14 dni od zawarcia umowy i zgadzam się na udostępnienie w tym czasie nagrania oraz materiałów cyfrowych. Przyjmuję do wiadomości, że po pełnym wykonaniu usługi oraz dostarczeniu treści cyfrowych utracę prawo odstąpienia w odpowiednim zakresie.',
        ],
        'digital_content' => [
            'version' => 'draft-digital-content-v1',
            'approved' => false,
            'statement' => 'Wyrażam zgodę na rozpoczęcie dostarczania nagrań, lekcji i materiałów cyfrowych objętych zamówieniem przed upływem 14 dni od zawarcia umowy, zgodnie z terminem dostępu wskazanym w zamówieniu. Przyjmuję do wiadomości, że z chwilą rozpoczęcia dostarczania tych treści za moją zgodą utracę prawo odstąpienia od umowy dotyczącej ich dostarczania, pod warunkiem przekazania mi przez PNE potwierdzenia umowy i tego oświadczenia na trwałym nośniku.',
        ],
        'digital_service' => [
            'version' => 'draft-digital-service-v1',
            'approved' => false,
            'statement' => 'Żądam rozpoczęcia świadczenia usługi dostępu do kursu przed upływem 14 dni od zawarcia umowy. Przyjmuję do wiadomości, że utracę prawo odstąpienia dopiero po pełnym wykonaniu odpłatnej usługi. W razie wcześniejszego odstąpienia rozliczenie świadczenia wykonanego na moje żądanie nastąpi na zasadach określonych w ustawie i Regulaminie.',
        ],
        'mixed' => [
            'version' => 'draft-mixed-v1',
            'approved' => false,
            'statement' => 'Wyraźnie żądam i zgadzam się na rozpoczęcie realizacji świadczeń objętych zamówieniem przed upływem 14 dni od zawarcia umowy, zgodnie z terminami wskazanymi w zamówieniu. Przyjmuję do wiadomości, że w odniesieniu do odpłatnych usług, w tym usług cyfrowych, utracę prawo odstąpienia po ich pełnym wykonaniu. W odniesieniu do odpłatnych treści cyfrowych niedostarczanych na nośniku materialnym przyjmuję do wiadomości, że utracę to prawo z chwilą rozpoczęcia ich dostarczania za moją zgodą, po przekazaniu mi wymaganego prawem potwierdzenia na trwałym nośniku.',
        ],
    ],
];
