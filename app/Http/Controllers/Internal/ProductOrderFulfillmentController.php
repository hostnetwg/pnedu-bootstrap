<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Services\ProductOrderFulfillmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductOrderFulfillmentController extends Controller
{
    public function fulfill(Request $request, int $id, ProductOrderFulfillmentService $fulfillment): JsonResponse
    {
        $recipientId = $request->integer('recipient_id') ?: null;
        $result = $fulfillment->fulfillOrder($id, 'manual', $recipientId);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function revoke(Request $request, int $id, ProductOrderFulfillmentService $fulfillment): JsonResponse
    {
        $recipientId = $request->integer('recipient_id') ?: null;
        $result = $fulfillment->revokeOrder($id, $recipientId);

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}
