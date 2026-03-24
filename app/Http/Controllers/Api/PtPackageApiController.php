<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MasterPtPackage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'PT Packages', description: 'Personal trainer package plans')]
class PtPackageApiController extends Controller
{
    #[OA\Get(
        path: '/api/pt-packages',
        tags: ['PT Packages'],
        summary: 'List all PT packages',
        parameters: [
            new OA\Parameter(name: 'gym_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'PT packages retrieved'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = MasterPtPackage::with(['gym:id,name', 'ptPackagePromos' => function ($q) {
            $q->whereNotNull('unique_code');
        }]);

        if ($request->filled('gym_id')) {
            $query->where('gym_id', $request->gym_id);
        }

        $packages = $query->latest()->get()->map(function ($p) {
            $promos = $p->ptPackagePromos->map(fn($promo) => [
                'unique_code' => $promo->unique_code,
                'type' => $promo->type,
                'value' => $promo->value,
            ]);

            return [
                'id' => $p->id,
                'name' => $p->name,
                'max_person' => $p->max_person,
                'duration_in_sessions' => $p->duration_in_sessions,
                'price' => $p->price,
                'gym' => $p->gym ? [
                    'id' => $p->gym->id,
                    'name' => $p->gym->name,
                ] : null,
                'active_promos' => $promos,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $packages,
            'message' => 'PT package data retrieved successfully',
        ]);
    }

    #[OA\Get(
        path: '/api/pt-packages/{id}',
        tags: ['PT Packages'],
        summary: 'Get PT package detail',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'PT package detail retrieved'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $package = MasterPtPackage::with(['gym', 'ptPackagePromos'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $package->id,
                'name' => $package->name,
                'max_person' => $package->max_person,
                'duration_in_sessions' => $package->duration_in_sessions,
                'price' => $package->price,
                'gym' => $package->gym ? [
                    'id' => $package->gym->id,
                    'name' => $package->gym->name,
                    'address' => $package->gym->address,
                ] : null,
                'promos' => $package->ptPackagePromos->map(fn($p) => [
                    'type' => $p->type,
                    'value' => $p->value,
                    'has_code' => !empty($p->unique_code),
                ]),
            ],
            'message' => 'PT package detail retrieved successfully',
        ]);
    }
}
