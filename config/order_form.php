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

];
