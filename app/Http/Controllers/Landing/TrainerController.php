<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class TrainerController extends Controller
{
    public function index(): JsonResponse
    {
        $trainers = User::role('Personal Trainer')
            ->with(['userDetail', 'ptDescriptions', 'ptImgUrls'])
            ->latest()
            ->get()
            ->map(function ($trainer) {
                return [
                    'id' => $trainer->id,
                    'name' => $trainer->name,
                    'role' => 'Personal Trainer',
                    'description' => $trainer->ptDescriptions->first()->description ?? null,
                    'images' => $trainer->ptImgUrls->pluck('img_url')->toArray(),
                    // UserDetail fields
                    'nik' => $trainer->userDetail->nik ?? null,
                    'birth_place' => $trainer->userDetail->birth_place ?? null,
                    'birth_date' => $trainer->userDetail->birth_date ?? null,
                    'gender' => $trainer->userDetail->gender ?? null,
                    'address' => $trainer->userDetail->address ?? null,
                    'phone_number' => $trainer->userDetail->phone_number ?? null,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $trainers,
            'message' => 'Trainer data retrieved successfully'
        ]);
    }
}
