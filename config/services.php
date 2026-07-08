<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'sns_topic_arn' => env('AWS_SNS_TOPIC_ARN', 'arn:aws:sns:eu-central-1:388786438877:ses-pne-system-events'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'certificate_registration' => [
        /** Cache statusu formularza (sekundy). 0 = wyłączony. Domyślnie 60 s — redukuje obciążenie API przy spike po webinarze. */
        'status_cache_ttl' => (int) env('CERT_REG_STATUS_CACHE_TTL', 60),
        /** Cache-Control max-age dla GET formularza (sekundy). 0 = wyłączony. Cloudflare może respektować ten nagłówek. */
        'page_cache_max_age' => (int) env('CERT_REG_PAGE_CACHE_MAX_AGE', 30),
        'page_cache_stale_while_revalidate' => (int) env('CERT_REG_PAGE_CACHE_STALE', 60),
        /** Sesja dla /certificate-registration/*: redis | inherit (domyślny SESSION_DRIVER). */
        'session_driver' => env('CERT_REG_SESSION_DRIVER', 'inherit'),
    ],

    'pneadm' => [
        'api_url' => env('PNEADM_API_URL'),
        'api_token' => env('PNEADM_API_TOKEN'),
        'timeout' => env('PNEADM_API_TIMEOUT', 30),
        // (do <img src="..."> z /storage). Lokalnie: PNEADM_PUBLIC_URL lub domyślnie adm.localhost:8083.
        'public_url' => rtrim(
            env('PNEADM_PUBLIC_URL') ?? (env('APP_ENV', 'production') === 'local'
                ? 'http://adm.localhost:8083'
                : 'https://adm.pnedu.pl'),
            '/'
        ),
        // Na produkcji domyślnie true: obrazy przez /media/pneadm/… (ten sam host co pnedu.pl).
        // Dev: false — przeglądarka ładuje bezpośrednio z adm.localhost.
        'media_proxy' => filter_var(
            env('PNEADM_MEDIA_PROXY', env('APP_ENV', 'production') === 'production'),
            FILTER_VALIDATE_BOOLEAN
        ),
    ],

    'gus_bir' => [
        'user_key' => env('GUS_BIR_USER_KEY'),
        'endpoint' => env('GUS_BIR_ENDPOINT', env('GUS_BIR_ENV', 'production') === 'test'
            ? 'https://wyszukiwarkaregontest.stat.gov.pl/wsBIR/UslugaBIRzewnPubl.svc'
            : 'https://wyszukiwarkaregon.stat.gov.pl/wsBIR/UslugaBIRzewnPubl.svc'),
        'timeout' => (int) env('GUS_BIR_TIMEOUT', 10),
    ],

    'payu' => [
        'sandbox' => filter_var(env('PAYU_SANDBOX', true), FILTER_VALIDATE_BOOLEAN),
        'pos_id' => env('PAYU_POS_ID'),
        'second_key' => env('PAYU_SECOND_KEY'),
        'client_id' => env('PAYU_CLIENT_ID'),
        'client_secret' => env('PAYU_CLIENT_SECRET'),
        // Sandbox ma osobne POS – jeśli 4421299 nie działa w sandbox, użyj produkcji (PAYU_SANDBOX=false)
        'base_url' => filter_var(env('PAYU_SANDBOX', true), FILTER_VALIDATE_BOOLEAN) ? 'https://secure.snd.payu.com' : 'https://secure.payu.com',
    ],

    'paynow' => [
        'sandbox' => filter_var(env('PAYNOW_SANDBOX', true), FILTER_VALIDATE_BOOLEAN),
        'api_key' => env('PAYNOW_API_KEY'),
        'signature_key' => env('PAYNOW_SIGNATURE_KEY'),
        'base_url' => filter_var(env('PAYNOW_SANDBOX', true), FILTER_VALIDATE_BOOLEAN) ? 'https://api.sandbox.paynow.pl' : 'https://api.paynow.pl',
    ],

    /*
    | Ident zamówień online (PayU/PayNow extOrderId):
    | - ident_prefix pusty: PNEDU_{nr}; z ident_segment=local → PNEDU_local_{nr}
    | - ident_prefix=PNEdu# → PNEdu#{nr}; z segmentem → PNEdu#{segment}_{nr}
    */
    'online_payment_order' => [
        'ident_prefix' => env('ONLINE_PAYMENT_ORDER_IDENT_PREFIX'),
        'ident_segment' => env('ONLINE_PAYMENT_ORDER_IDENT_SEGMENT'),
    ],

    // Sendy – te same zmienne i wartości domyślne co w pneadm-bootstrap (config/sendy.php)
    'sendy' => [
        'url' => env('SENDY_URL', env('SENDY_BASE_URL', 'https://sendyhost.net')),
        'api_key' => env('SENDY_API_KEY', 'QWVN3gYyibFsPWh39Til'),
        /** Nazwa parametru POST = tag pola custom (TEXT) z datą szkolenia RRRR-MM-DD — segment „data is …” w Sendy */
        'training_date_field' => env('SENDY_TRAINING_DATE_FIELD', 'data'),
        'timeout' => (int) env('SENDY_HTTP_TIMEOUT', 15),
    ],

    'google_tag_manager' => [
        'id' => env('GOOGLE_TAG_MANAGER_ID', 'GTM-5ZQ4MM74'),
    ],

    'google_analytics' => [
        'id' => env('GOOGLE_ANALYTICS_ID'),
    ],

    'facebook_pixel' => [
        'id' => env('FACEBOOK_PIXEL_ID', '378657018971423'),
    ],

    // Wywołania server-to-server z panelu adm (pneadm) — invalidacja cache oferty na pnedu.
    'internal_api' => [
        'token' => env('PNEDU_INTERNAL_API_TOKEN', env('PNEADM_API_TOKEN')),
    ],

];
