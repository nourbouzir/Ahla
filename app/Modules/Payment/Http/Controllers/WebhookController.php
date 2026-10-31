<?php

namespace App\Modules\Payment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payment\Enums\PaymentStatus;
use App\Modules\Payment\Exceptions\PaymentFailedException;
use App\Modules\Payment\Models\Payment;
use App\Modules\Payment\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POST /api/payments/webhook - the provider tells us a payment changed.
 * The body must carry an HMAC-SHA256 signature made with the shared secret,
 * otherwise anyone could mark a booking as paid.
 */
class WebhookController extends Controller
{
    public function handle(Request $request, PaymentService $payments): JsonResponse
    {
        $secret = (string) config('ahla.payment.webhook_secret');

        // No secret configured = webhook disabled, never "open".
        if ($secret === '') {
            return response()->json(['error' => 'webhook disabled'], 503);
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        if (! hash_equals($expected, (string) $request->header('X-Ahla-Signature'))) {
            return response()->json(['error' => 'bad signature'], 401);
        }

        $data = $request->validate([
            'reference' => ['required', 'string'],
            'status' => ['required', 'string'],
        ]);

        $status = PaymentStatus::tryFrom($data['status']);
        $payment = Payment::query()->where('gateway_ref', $data['reference'])->first();

        if ($status === null || $payment === null) {
            return response()->json(['error' => 'unknown payment or status'], 422);
        }

        try {
            $payment = $payments->applyGatewayStatus($payment, $status);
        } catch (PaymentFailedException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        }

        return response()->json(['status' => $payment->status->value]);
    }
}
