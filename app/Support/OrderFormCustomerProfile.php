<?php

namespace App\Support;

/**
 * Profil klienta formularza V2 (school / organisation / person / jdg)
 * na podstawie NIP nabywcy i danych odbiorcy.
 */
class OrderFormCustomerProfile
{
    public const SCHOOL = 'school';

    public const ORGANISATION = 'organisation';

    public const PERSON = 'person';

    public const JDG = 'jdg';

    public const ALL = [
        self::SCHOOL,
        self::ORGANISATION,
        self::PERSON,
        self::JDG,
    ];

    /**
     * @return self::SCHOOL|self::ORGANISATION|self::PERSON
     */
    public static function fromBuyerAndRecipient(
        ?string $buyerNip,
        ?string $recipientNip = null,
        ?string $recipientName = null
    ): string {
        $buyerDigits = preg_replace('/\D+/', '', (string) $buyerNip) ?? '';
        if ($buyerDigits === '') {
            return self::PERSON;
        }

        $recipientDigits = preg_replace('/\D+/', '', (string) $recipientNip) ?? '';
        $recipientLabel = trim((string) $recipientName);

        if ($recipientDigits !== '' || $recipientLabel !== '') {
            return self::SCHOOL;
        }

        return self::ORGANISATION;
    }

    /**
     * buyer_type zapisywany w formularzu (person | organisation).
     */
    public static function buyerTypeForProfile(string $profile): string
    {
        return $profile === self::PERSON ? 'person' : 'organisation';
    }

    public static function isConsumerProtected(string $profile): bool
    {
        return in_array($profile, [self::PERSON, self::JDG], true);
    }
}
