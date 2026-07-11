<?php

namespace App\Support;

use Illuminate\Http\Request;

final class OrderFormGateway
{
    public const RESOLVED_VARIANT_ATTRIBUTE = 'order_form_resolved_variant';

    public const QUERY_PARAM = 'form_variant';

    /**
     * @param  array{show_order_form?: bool, show_order_form_v2?: bool, default_signup_order_form_variant?: string}  $displayOptions
     */
    public function resolveVariant(Request $request, array $displayOptions): string
    {
        $preferred = $request->filled(self::QUERY_PARAM)
            ? OrderFormVariant::normalize((string) $request->query(self::QUERY_PARAM))
            : (string) ($displayOptions['default_signup_order_form_variant'] ?? OrderFormVariant::LEGACY);

        return OrderFormVariant::resolveAvailable($preferred, $displayOptions);
    }

    public function markResolvedVariant(Request $request, string $variant): void
    {
        $request->attributes->set(
            self::RESOLVED_VARIANT_ATTRIBUTE,
            OrderFormVariant::normalize($variant)
        );
    }

    public static function resolvedVariantFromRequest(Request $request): ?string
    {
        $value = $request->attributes->get(self::RESOLVED_VARIANT_ATTRIBUTE);

        return is_string($value) && $value !== ''
            ? OrderFormVariant::normalize($value)
            : null;
    }

    /**
     * @param  array<string, scalar|null>  $query
     * @return array<string, scalar|null>
     */
    public static function mergeGatewayQuery(string $variant, array $query = []): array
    {
        return array_merge($query, [
            self::QUERY_PARAM => OrderFormVariant::normalize($variant),
        ]);
    }
}
