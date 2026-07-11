<?php

namespace App\Support;

final class OrderFormTestData
{
    /**
     * Domyślny zestaw danych do przycisku „Wypełnij dane testowe” (dev / tryb testowy).
     *
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'buyer_type' => 'organisation',
            'customer_profile' => 'school',
            'contact_name' => 'Waldemar Grabowski',
            'contact_first_name' => 'Waldemar',
            'contact_last_name' => 'Grabowski',
            'contact_phone' => '501 654 274',
            'contact_email' => 'waldemar.grabowski@zdalna-lekcja.pl',
            'buyer_name' => 'Platforma Nowoczesnej Edukacji Waldemar Grabowski',
            'buyer_address' => 'ul. Andrzeja Zamoyskiego 30/14',
            'buyer_postcode' => '09-320',
            'buyer_city' => 'Bieżuń',
            'buyer_nip' => '7392137630',
            'recipient_name' => 'NOWATORNIA Łukasz Grabowski',
            'recipient_address' => 'UL. HANSA CHRISTIANA ANDERSENA 2/230',
            'recipient_postcode' => '01-911',
            'recipient_city' => 'WARSZAWA',
            'recipient_nip' => '1182307502',
            'buyer_person_first_name' => 'Waldemar',
            'buyer_person_last_name' => 'Grabowski',
            'participant_first_name' => 'Waldemar',
            'participant_last_name' => 'Grabowski',
            'participant_email' => 'waldemar.grabowski@hostnet.pl',
            'invoice_notes' => 'Dane testowe - Waldek',
            'payment_type' => 'deferred',
            'payment_terms' => 14,
            'payment_gateway' => 'payu',
        ];
    }
}
