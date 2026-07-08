<?php

namespace App\Services;

use App\Models\FormOrder;
use Illuminate\Http\Request;

/**
 * Identyfikacja odbiorcy (Podmiot3) w publicznym formularzu zamówienia PNEDU.
 *
 * NIP odbiorcy — zawsze (GUS, klasyczny odbiorca). Identyfikator wewnętrzny (IDWew) — opcjonalnie,
 * dodatkowo, gdy organizacja wymaga oznaczenia oddziału/jednostki w KSeF (FA(3)).
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
        if ($nip === '' || strlen($nip) !== 10) {
            return [
                'field' => 'recipient_nip',
                'message' => 'NIP odbiorcy jest wymagany (10 cyfr), jeśli podano dane odbiorcy.',
            ];
        }

        if (! $this->hasRecipientInternalId($request)) {
            return null;
        }

        $buyerDigits = preg_replace('/\D+/', '', (string) $buyerNip);
        if ($buyerDigits === '' || strlen($buyerDigits) !== 10) {
            return [
                'field' => 'recipient_internal_id',
                'message' => 'Aby podać identyfikator wewnętrzny, najpierw uzupełnij poprawny NIP nabywcy.',
            ];
        }

        if (! $this->normalizeIdwew((string) $request->input('recipient_internal_id', ''), $buyerDigits)) {
            return [
                'field' => 'recipient_internal_id',
                'message' => 'Podaj identyfikator wewnętrzny: 5 cyfr oddziału (np. 00001) lub pełny IDWew z KSeF (NIP-00001).',
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

        if (preg_match('/^[0-9]{10}-([0-9]{5})$/', $identifier, $matches)) {
            $prefill['recipient_internal_id'] = $matches[1];
        } else {
            $prefill['recipient_internal_id'] = $identifier;
        }

        return $prefill;
    }

    /**
     * Normalizacja IDWew do postaci kanonicznej KSeF: NIP (10 cyfr) + „-” + 5 cyfr.
     */
    public function normalizeIdwew(string $raw, string $buyerNipDigits): ?string
    {
        $raw = trim($raw);
        $buyerNipDigits = preg_replace('/\D+/', '', $buyerNipDigits) ?? '';

        if ($raw === '' || strlen($buyerNipDigits) !== 10) {
            return null;
        }

        if (preg_match('/^([0-9]{10})-([0-9]{5})$/', $raw, $matches)) {
            if ($matches[1] !== $buyerNipDigits) {
                return null;
            }

            return $matches[1].'-'.$matches[2];
        }

        if (preg_match('/^[0-9]{5}$/', $raw)) {
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
