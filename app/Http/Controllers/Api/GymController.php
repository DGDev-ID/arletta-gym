<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MasterGym;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Gyms', description: 'Master gym endpoints')]
class GymController extends Controller
{
    #[OA\Get(
        path: '/api/gyms',
        tags: ['Gyms'],
        summary: 'List master gyms',
        responses: [
            new OA\Response(response: 200, description: 'Gyms retrieved'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $gyms = MasterGym::select('id', 'name', 'address')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $gyms,
            'message' => 'Gyms retrieved successfully',
        ]);
    }
}
