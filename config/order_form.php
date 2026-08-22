<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Publiczny formularz zamówienia (PNEDU)
    |--------------------------------------------------------------------------
    */

    /** Pole IDWew (identyfikator wewnętrzny KSeF) w sekcji ODBIORCA — domyślnie ukryte. */
    'show_recipient_internal_id' => env('ORDER_FORM_SHOW_RECIPIENT_INTERNAL_ID', false),

    /**
     * Maks. liczba uczestników na jednym zamówieniu (szkoła / instytucja / firma).
     * Osoba prywatna zawsze 1. Rabat grupowy — przyszły etap (np. przy wariancie cenowym).
     */
    'max_participants' => (int) env('ORDER_FORM_MAX_PARTICIPANTS', 50),

    /**
     * Po ilu minutach nieopłacone online (awaiting_payment) przestaje blokować e-mail uczestnika.
     */
    'online_abandonment_minutes' => (int) env('ORDER_FORM_ONLINE_ABANDONMENT_MINUTES', 60),

    /** Powód operacyjnego anulowania przy przejściu klienta na fakturę odroczoną. */
    'online_superseded_cancel_reason' => 'zastąpione zamówieniem odroczonym',

];
