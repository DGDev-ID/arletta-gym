<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Services\MidtransService;
use App\Http\Services\PaymentService;
use App\Http\Services\UpdateStatusTransactionService;
use App\Http\Services\WhatsappBlastService;
use App\Models\Signature;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Payments', description: 'Payment & transaction endpoints')]
class PaymentController extends Controller
{
    #[OA\Post(
        path: '/api/payments/create',
        tags: ['Payments'],
        summary: 'Create a payment for membership or PT package',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['gym_id', 'transaction_type', 'type_id', 'payment_method'],
                properties: [
                    new OA\Property(property: 'gym_id', type: 'integer'),
                    new OA\Property(property: 'transaction_type', type: 'string', enum: ['membership', 'pt']),
                    new OA\Property(property: 'type_id', type: 'integer', description: 'membership_id or pt_package_id'),
                    new OA\Property(property: 'payment_method', type: 'string', enum: ['manual', 'va', 'qris']),
                    new OA\Property(property: 'payment_type', type: 'string', enum: ['full_payment', 'dp_payment'], description: 'Required for PT'),
                    new OA\Property(property: 'dp_percent', type: 'number', example: 30, description: 'Required if dp_payment'),
                    new OA\Property(property: 'promo_code', type: 'string'),
                    new OA\Property(property: 'signature_data', type: 'string', description: 'Base64 signature for membership purchase'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Payment created',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'transaction_id', type: 'integer'),
                            new OA\Property(property: 'snap_token', type: 'string'),
                            new OA\Property(property: 'status', type: 'string'),
                        ]),
                    ]
                )
            ),
        ]
    )]
    public function create(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'gym_id' => 'required|exists:master_gyms,id',
            'transaction_type' => 'required|in:membership,pt',
            'type_id' => 'required|integer',
            'payment_method' => 'required|in:manual,va,qris',
            'payment_type' => 'nullable|required_if:transaction_type,pt|in:full_payment,dp_payment',
            'dp_percent' => 'nullable|required_if:payment_type,dp_payment|numeric|min:0|max:100',
            'promo_code' => 'nullable|string',
            'signature_data' => 'nullable|string',
            // 'trainer_id' => 'nullable|exists:users,id',
        ]);

        $data['user_id'] = $user->id;

        // Process payment using existing PaymentService
        $result = PaymentService::processPayment($data);

        // Generate Midtrans snap token if needed
        if (in_array($data['payment_method'], ['va', 'qris'])) {
            $transaction = Transaction::find($result['transaction_id']);
            $midtransService = new MidtransService();
            $snapToken = $midtransService->getSnapToken($transaction, $user);
            $transaction->snap_token = $snapToken;
            $transaction->save();
            $result['snap_token'] = $snapToken;
        }

        // Store signature if provided (for membership purchase)
        if (!empty($data['signature_data'])) {
            Signature::create([
                'user_id' => $user->id,
                'transaction_id' => $result['transaction_id'],
                'signature_data' => $data['signature_data'],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => [
                    'transaction_type' => $data['transaction_type'],
                    'type_id' => $data['type_id'],
                    'timestamp' => now()->toISOString(),
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => 'Payment created successfully.',
        ]);
    }

    #[OA\Post(
        path: '/api/payments/webhook',
        tags: ['Payments'],
        summary: 'Midtrans payment notification webhook',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'order_id', type: 'string'),
                    new OA\Property(property: 'transaction_status', type: 'string'),
                    new OA\Property(property: 'fraud_status', type: 'string'),
                    new OA\Property(property: 'signature_key', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Webhook processed'),
        ]
    )]
    public function webhook(Request $request, WhatsappBlastService $whatsappBlastService): JsonResponse
    {
        $payload = $request->all();

        Log::info('Midtrans Webhook', $payload);

        // Verify signature
        $serverKey = config('midtrans.server_key');
        $orderId = $payload['order_id'] ?? '';
        $statusCode = $payload['status_code'] ?? '';
        $grossAmount = $payload['gross_amount'] ?? '';
        $signatureKey = $payload['signature_key'] ?? '';

        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        if ($signatureKey !== $expectedSignature) {
            Log::warning('Midtrans Webhook: Invalid signature', ['order_id' => $orderId]);
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        // Extract transaction ID from order_id (format: {id}-{timestamp})
        $transactionId = explode('-', $orderId)[0] ?? null;
        $transaction = Transaction::findOrFail($transactionId);

        $transactionStatus = $payload['transaction_status'] ?? '';
        $fraudStatus = $payload['fraud_status'] ?? 'accept';

        try {
            DB::transaction(function () use ($transaction, $transactionStatus, $fraudStatus, $whatsappBlastService) {
                if ($transactionStatus === 'capture' || $transactionStatus === 'settlement') {
                    if ($fraudStatus === 'accept') {
                        UpdateStatusTransactionService::makeSuccess($transaction, null, $whatsappBlastService);
                    }
                } elseif (in_array($transactionStatus, ['cancel', 'deny', 'expire'])) {
                    UpdateStatusTransactionService::makeFailed($transaction);
                }
                // 'pending' status — do nothing, keep as pending
            });
        } catch (\Exception $e) {
            Log::error('Midtrans Webhook Error', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['message' => 'OK']);
    }
}
