<?php

namespace App\Services;

use App\Models\FormOrder;
use Illuminate\Http\Request;

/**
 * Identyfikacja odbiorcy (Podmiot3) w publicznym formularzu zamówienia PNEDU.
 *
 * NIP odbiorcy i identyfikator wewnętrzny (IDWew) są opcjonalne. GUS BIR wyszukuje wyłącznie po NIP,
 * więc przy IDWew nie ma pobierania z GUS. Pełny IDWew z KSeF (NIP-00001) zapisujemy do
 * ksef_additional_entity_id_type / ksef_additional_entity_identifier.
 */
class OrderFormRecipientIdentityService
{
    public const KSEF_SOURCE_NONE = 'none';

    public const KSEF_SOURCE_RECIPIENT = 'recipient';

    public const KSEF_ID_TYPE_IDWEW = 'IDWew';

    public function hasRecipientPhysicalData(Request $request): bool
    {
        return $request->filled('recipient_name')
            || $request->filled('recipient_address')
            || $request->filled('recipient_postcode')
            || $request->filled('recipient_city')
            || $request->filled('recipient_nip')
            || $request->filled('recipient_internal_id');
    }

    public function hasRecipientInternalId(Request $request): bool
    {
        return trim((string) $request->input('recipient_internal_id', '')) !== '';
    }

    /**
     * @return array{field: string, message: string}|null
     */
    public function validateRecipientIdentity(Request $request, ?string $buyerNip): ?array
    {
        if (! $this->hasRecipientPhysicalData($request)) {
            return null;
        }

        $nip = preg_replace('/\D+/', '', (string) $request->input('recipient_nip', ''));
        if ($nip !== '' && strlen($nip) !== 10) {
            return [
                'field' => 'recipient_nip',
                'message' => 'NIP odbiorcy musi składać się z 10 cyfr.',
            ];
        }

        if (! $this->hasRecipientInternalId($request)) {
            return null;
        }

        $rawInternalId = (string) $request->input('recipient_internal_id', '');
        $buyerDigits = preg_replace('/\D+/', '', (string) $buyerNip) ?? '';

        if (preg_match('/^[0-9]{5}$/', trim($rawInternalId)) && strlen($buyerDigits) !== 10) {
            return [
                'field' => 'recipient_internal_id',
                'message' => 'Aby podać sam numer oddziału (5 cyfr), najpierw uzupełnij poprawny NIP nabywcy.',
            ];
        }

        if (! $this->normalizeIdwew($rawInternalId, $buyerDigits)) {
            return [
                'field' => 'recipient_internal_id',
                'message' => 'Podaj pełny identyfikator wewnętrzny z KSeF, np. 1234567890-00001.',
            ];
        }

        return null;
    }

    /**
     * @return array{
     *     recipient_nip: ?string,
     *     ksef_entity_source: string,
     *     ksef_additional_entity_role: ?string,
     *     ksef_additional_entity_id_type: ?string,
     *     ksef_additional_entity_identifier: ?string
     * }
     */
    public function resolveStoragePayload(Request $request, ?string $buyerNip): array
    {
        $empty = [
            'recipient_nip' => null,
            'ksef_entity_source' => self::KSEF_SOURCE_NONE,
            'ksef_additional_entity_role' => null,
            'ksef_additional_entity_id_type' => null,
            'ksef_additional_entity_identifier' => null,
        ];

        if (! $this->hasRecipientPhysicalData($request)) {
            return $empty;
        }

        $nip = preg_replace('/\D+/', '', (string) $request->input('recipient_nip', ''));
        $payload = [
            'recipient_nip' => $nip !== '' ? $nip : null,
            'ksef_entity_source' => self::KSEF_SOURCE_NONE,
            'ksef_additional_entity_role' => null,
            'ksef_additional_entity_id_type' => null,
            'ksef_additional_entity_identifier' => null,
        ];

        if (! $this->hasRecipientInternalId($request)) {
            return $payload;
        }

        $buyerDigits = preg_replace('/\D+/', '', (string) $buyerNip);
        $idwew = $this->normalizeIdwew((string) $request->input('recipient_internal_id', ''), $buyerDigits);
        if ($idwew === null) {
            return $payload;
        }

        $payload['ksef_entity_source'] = self::KSEF_SOURCE_RECIPIENT;
        $payload['ksef_additional_entity_role'] = 'odbiorca';
        $payload['ksef_additional_entity_id_type'] = self::KSEF_ID_TYPE_IDWEW;
        $payload['ksef_additional_entity_identifier'] = $idwew;

        return $payload;
    }

    /**
     * @return array{recipient_nip: ?string, recipient_internal_id: ?string}
     */
    public function prefillFromFormOrder(FormOrder $order): array
    {
        $prefill = [
            'recipient_nip' => $order->recipient_nip,
            'recipient_internal_id' => null,
        ];

        $idType = (string) ($order->ksef_additional_entity_id_type ?? '');
        $identifier = trim((string) ($order->ksef_additional_entity_identifier ?? ''));

        if ($idType !== self::KSEF_ID_TYPE_IDWEW || $identifier === '') {
            return $prefill;
        }

        $prefill['recipient_internal_id'] = $identifier;

        return $prefill;

        return $prefill;
    }

    /**
     * Normalizacja IDWew do postaci kanonicznej KSeF: 10 cyfr NIP + „-” + 5 cyfr.
     * Pełny identyfikator z KSeF zapisujemy bez wymagania zgodności z NIP nabywcy.
     * Sam suffix 5-cyfrowy składamy z NIP nabywcy, gdy jest dostępny.
     */
    public function normalizeIdwew(string $raw, string $buyerNipDigits): ?string
    {
        $raw = trim($raw);
        $buyerNipDigits = preg_replace('/\D+/', '', $buyerNipDigits) ?? '';

        if ($raw === '') {
            return null;
        }

        if (preg_match('/^([0-9]{10})-([0-9]{5})$/', $raw, $matches)) {
            return $matches[1].'-'.$matches[2];
        }

        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        if (strlen($digits) === 15) {
            return substr($digits, 0, 10).'-'.substr($digits, 10, 5);
        }

        if (preg_match('/^[0-9]{5}$/', $raw)) {
            if (strlen($buyerNipDigits) !== 10) {
                return null;
            }

            return $buyerNipDigits.'-'.$raw;
        }

        return null;
    }

    public function formatIdwewForDisplay(?string $identifier): ?string
    {
        $identifier = trim((string) $identifier);
        if ($identifier === '') {
            return null;
        }

        if (preg_match('/^[0-9]{10}-[0-9]{5}$/', $identifier)) {
            return $identifier;
        }

        if (preg_match('/^([0-9]{10})([0-9]{5})$/', $identifier, $matches)) {
            return $matches[1].'-'.$matches[2];
        }

        return $identifier;
    }
}
