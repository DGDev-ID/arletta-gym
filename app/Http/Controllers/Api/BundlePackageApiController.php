<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MasterBundlePackage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Bundle Packages', description: 'Bundle package plans (membership + PT sessions)')]
class BundlePackageApiController extends Controller
{
    #[OA\Get(
        path: '/api/bundle-packages',
        tags: ['Bundle Packages'],
        summary: 'List all bundle packages',
        parameters: [
            new OA\Parameter(name: 'gym_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Bundle packages retrieved'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = MasterBundlePackage::with(['gym:id,name', 'bundlePackagePromos']);

        if ($request->filled('gym_id')) {
            $query->where('gym_id', $request->gym_id);
        }

        $packages = $query->latest()->get()->map(function ($b) {
            $promos = $b->bundlePackagePromos->map(fn($p) => [
                'type'     => $p->type,
                'value'    => $p->value,
                'has_code' => !empty($p->unique_code),
                'code'     => $p->unique_code, // null jika promo global (otomatis aktif)
            ]);

            return [
                'id'                          => $b->id,
                'name'                        => $b->name,
                'description'                 => $b->description,
                'membership_duration_in_days' => $b->membership_duration_in_days,
                'pt_sessions'                 => $b->pt_sessions,
                'price'                       => $b->price,
                'gym'                         => $b->gym ? [
                    'id'   => $b->gym->id,
                    'name' => $b->gym->name,
                ] : null,
                'promos' => $promos,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $packages,
            'message' => 'Bundle package data retrieved successfully',
        ]);
    }

    #[OA\Get(
        path: '/api/bundle-packages/{id}',
        tags: ['Bundle Packages'],
        summary: 'Get bundle package detail',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Bundle package detail retrieved'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $bundle = MasterBundlePackage::with(['gym', 'bundlePackagePromos'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'                          => $bundle->id,
                'name'                        => $bundle->name,
                'description'                 => $bundle->description,
                'membership_duration_in_days' => $bundle->membership_duration_in_days,
                'pt_sessions'                 => $bundle->pt_sessions,
                'price'                       => $bundle->price,
                'gym'                         => $bundle->gym ? [
                    'id'      => $bundle->gym->id,
                    'name'    => $bundle->gym->name,
                    'address' => $bundle->gym->address,
                ] : null,
                'promos' => $bundle->bundlePackagePromos->map(fn($p) => [
                    'type'     => $p->type,
                    'value'    => $p->value,
                    'has_code' => !empty($p->unique_code),
                ]),
            ],
            'message' => 'Bundle package detail retrieved successfully',
        ]);
    }
}
