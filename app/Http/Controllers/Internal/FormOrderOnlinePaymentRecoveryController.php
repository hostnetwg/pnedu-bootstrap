<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\FormOrder;
use App\Services\FormOrderOnlinePaymentRecoveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FormOrderOnlinePaymentRecoveryController extends Controller
{
    public function send(Request $request, int $id, FormOrderOnlinePaymentRecoveryService $service): JsonResponse
    {
        $order = FormOrder::find($id);
        if (! $order) {
            return response()->json([
                'success' => false,
                'error' => 'Zamówienie nie zostało znalezione.',
                'code' => 'not_found',
            ], 404);
        }

        $allowResend = $request->boolean('allow_resend', true);
        $result = $service->sendRecoveryEmail($order, allowResend: $allowResend);

        $status = ($result['success'] ?? false) ? 200 : match ($result['code'] ?? '') {
            'not_found' => 404,
            'not_eligible', 'already_sent', 'disabled', 'no_recipients', 'course_missing', 'online_payment_missing' => 422,
            default => 500,
        };

        return response()->json($result, $status);
    }
}
