<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Models\UserGym;

class ManageRescheduleController extends Controller
{
    public function index(Request $request)
    {
        $query = UserGym::with(['user', 'gym'])
            ->whereNotNull('membership_start_at')
            ->whereDate('membership_start_at', '>', Carbon::now()->toDateString())
            ->orderBy('membership_start_at', 'asc');

        return Inertia::render('Management/Reschedule/Index', [
            'userGyms' => $query->paginate(10)->withQueryString(),
        ]);
    }

    public function update(Request $request)
    {
        // 1. Tambahkan validasi untuk membership_end_at
        $validated = $request->validate([
            'user_id'             => ['required', 'exists:users,id'],
            'gym_id'              => ['required', 'exists:master_gyms,id'],
            'membership_start_at' => ['required', 'date'],
            'membership_end_at'   => ['required', 'date'], 
        ]);

        $userGym = UserGym::where('user_id', $validated['user_id'])
            ->where('gym_id', $validated['gym_id'])
            ->firstOrFail();

        if (!$userGym->membership_start_at || !$userGym->membership_end_at) {
            return response()->json(['message' => 'User gym membership dates not set'], 422);
        }

        // startOfDay() dan endOfDay() memastikan waktunya tepat dari 00:00:00 hingga 23:59:59
        $userGym->membership_start_at = Carbon::parse($validated['membership_start_at'])->startOfDay();
        $userGym->membership_end_at   = Carbon::parse($validated['membership_end_at'])->endOfDay();
        $userGym->save();

        return response()->json(['message' => 'Reschedule berhasil', 'user_gym' => $userGym]);
    }
}
