<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Models\MasterMembership;
use Illuminate\Http\JsonResponse;

class MembershipController extends Controller
{
    public function index(): JsonResponse
    {
        $memberships = MasterMembership::select('id', 'name', 'price')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $memberships,
            'message' => 'Membership data retrieved successfully'
        ]);
    }
}
