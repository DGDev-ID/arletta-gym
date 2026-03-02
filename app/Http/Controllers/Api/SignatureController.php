<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Signature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Signatures', description: 'Digital signature storage')]
class SignatureController extends Controller
{
    #[OA\Post(
        path: '/api/signatures',
        tags: ['Signatures'],
        summary: 'Store a digital signature',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['signature_data'],
                properties: [
                    new OA\Property(property: 'signature_data', type: 'string', description: 'Base64 encoded signature image'),
                    new OA\Property(property: 'transaction_id', type: 'integer'),
                    new OA\Property(property: 'metadata', type: 'object'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Signature stored',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'id', type: 'integer'),
                        ]),
                    ]
                )
            ),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'signature_data' => 'required|string',
            'transaction_id' => 'nullable|exists:transactions,id',
            'metadata' => 'nullable|array',
        ]);

        $signature = Signature::create([
            'user_id' => $request->user()->id,
            'transaction_id' => $validated['transaction_id'] ?? null,
            'signature_data' => $validated['signature_data'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => $validated['metadata'] ?? [
                'timestamp' => now()->toISOString(),
            ],
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $signature->id,
            ],
            'message' => 'Signature stored successfully.',
        ], 201);
    }
}
