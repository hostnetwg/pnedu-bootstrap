<?php

namespace App\Http\Controllers;

use App\Services\Analytics\GusAnalyticsTracker;
use App\Services\GusBirService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class GusLookupController extends Controller
{
    public function __invoke(Request $request, GusBirService $gusBir, GusAnalyticsTracker $gusAnalytics): JsonResponse
    {
        $validated = $request->validate([
            'nip' => ['required', 'string'],
            'target' => ['nullable', 'in:buyer,recipient'],
            'course_id' => ['nullable', 'integer', 'min:1'],
            'price_variant_id' => ['nullable', 'integer', 'min:1'],
            'form_session_id' => ['nullable', 'uuid'],
        ]);

        $target = $gusAnalytics->resolveTarget($validated['target'] ?? null);
        $startedAt = microtime(true);

        $nip = $gusBir->normalizeNip((string) $validated['nip']);
        if ($nip === null) {
            $gusAnalytics->trackValidationError($request, $target);

            throw ValidationException::withMessages([
                'nip' => 'Wpisz poprawny NIP składający się z 10 cyfr.',
            ]);
        }

        $gusAnalytics->trackLookupStarted($request, $target, $startedAt);

        try {
            $data = $gusBir->lookupByNip($nip);
        } catch (RuntimeException $e) {
            $latencyMs = (int) round((microtime(true) - $startedAt) * 1000);
            $message = $e->getMessage();
            $errorType = str_contains(strtolower($message), 'timeout') ? 'timeout' : 'gus_unavailable';

            Log::warning('GUS BIR lookup failed', [
                'target' => $target,
                'message' => $message,
            ]);

            $gusAnalytics->trackLookupError($request, $target, $errorType, 503, $latencyMs, true);

            return response()->json([
                'success' => false,
                'message' => 'Nie udało się teraz pobrać danych z GUS. Spróbuj ponownie lub wpisz dane ręcznie.',
            ], 503);
        } catch (\Throwable $e) {
            $latencyMs = (int) round((microtime(true) - $startedAt) * 1000);

            Log::error('GUS BIR lookup exception', [
                'target' => $target,
                'message' => $e->getMessage(),
            ]);

            $gusAnalytics->trackLookupError($request, $target, 'server_error', 503, $latencyMs, true);

            return response()->json([
                'success' => false,
                'message' => 'Nie udało się teraz pobrać danych z GUS. Spróbuj ponownie lub wpisz dane ręcznie.',
            ], 503);
        }

        if ($data === null) {
            $latencyMs = (int) round((microtime(true) - $startedAt) * 1000);
            $gusAnalytics->trackLookupError($request, $target, 'not_found', 404, $latencyMs, true);

            return response()->json([
                'success' => false,
                'message' => 'Nie znaleziono podmiotu o podanym NIP.',
            ], 404);
        }

        $latencyMs = (int) round((microtime(true) - $startedAt) * 1000);
        $gusAnalytics->trackLookupSuccess(
            $request,
            $target,
            $latencyMs,
            $gusAnalytics->countReturnedFields($data),
            'gus',
            'exact_match',
        );

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
