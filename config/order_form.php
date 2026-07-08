<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Publiczny formularz zamówienia (PNEDU)
    |--------------------------------------------------------------------------
    */

    /** Pole IDWew (identyfikator wewnętrzny KSeF) w sekcji ODBIORCA — domyślnie ukryte. */
    'show_recipient_internal_id' => env('ORDER_FORM_SHOW_RECIPIENT_INTERNAL_ID', false),

];
