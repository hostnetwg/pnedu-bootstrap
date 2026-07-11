<?php

namespace App\Support;

final class OrderFormV2ParticipantDefaults
{
    /**
     * Domyślny stan przełącznika „Zamawiający jest równocześnie uczestnikiem szkolenia”.
     *
     * Uzasadnienie (analiza 5793 zamówień form_orders + form_order_participants, 07/2026):
     * - dopasowanie e-mail zamawiającego i uczestnika: ~61% (nie wystarcza — często ten sam inbox szkoły),
     * - dopasowanie imienia i nazwiska: ~38%,
     * - heurystyka „ta sama osoba” (identyczne imię i nazwisko lub e-mail + imię i nazwisko w polu zamawiającego): ~39% ogółem,
     * - zamówienia bez NIP (proxy osoby prywatnej): ~99% ta sama osoba,
     * - zamówienia z NIP / odbiorcą (proxy szkoły i firm): ~36% ta sama osoba.
     */
    public static function isParticipantSameAsContactDefault(string $customerProfile): bool
    {
        return $customerProfile === 'person';
    }
}
