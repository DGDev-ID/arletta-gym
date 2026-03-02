<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MasterMembership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Memberships', description: 'Membership plans')]
class MembershipApiController extends Controller
{
    #[OA\Get(
        path: '/api/memberships',
        tags: ['Memberships'],
        summary: 'List all membership plans',
        parameters: [
            new OA\Parameter(name: 'gym_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Memberships retrieved'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = MasterMembership::with(['gym:id,name', 'membershipPromos' => function ($q) {
            $q->whereNull('unique_code'); // only global (non-code) promos
        }]);

        if ($request->filled('gym_id')) {
            $query->where('gym_id', $request->gym_id);
        }

        $memberships = $query->latest()->get()->map(function ($m) {
            $activePromos = $m->membershipPromos->map(fn($p) => [
                'type' => $p->type,
                'value' => $p->value,
            ]);

            return [
                'id' => $m->id,
                'name' => $m->name,
                'duration_in_days' => $m->duration_in_days,
                'price' => $m->price,
                'gym' => $m->gym ? [
                    'id' => $m->gym->id,
                    'name' => $m->gym->name,
                ] : null,
                'active_promos' => $activePromos,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $memberships,
            'message' => 'Membership data retrieved successfully',
        ]);
    }

    #[OA\Get(
        path: '/api/memberships/{id}',
        tags: ['Memberships'],
        summary: 'Get membership plan detail',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Membership detail retrieved'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $membership = MasterMembership::with(['gym', 'membershipPromos'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $membership->id,
                'name' => $membership->name,
                'duration_in_days' => $membership->duration_in_days,
                'price' => $membership->price,
                'gym' => $membership->gym ? [
                    'id' => $membership->gym->id,
                    'name' => $membership->gym->name,
                    'address' => $membership->gym->address,
                ] : null,
                'promos' => $membership->membershipPromos->map(fn($p) => [
                    'type' => $p->type,
                    'value' => $p->value,
                    'has_code' => !empty($p->unique_code),
                ]),
            ],
            'message' => 'Membership detail retrieved successfully',
        ]);
    }
}
