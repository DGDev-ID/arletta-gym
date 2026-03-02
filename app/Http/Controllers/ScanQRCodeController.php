<?php

namespace App\Http\Controllers;

use App\Models\GymAdmin;
use App\Models\MasterGym;
use App\Models\User;
use App\Models\UserGym;
use App\Models\UserGymScan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ScanQRCodeController extends Controller
{
    /**
     * Show the Scan QR Code page.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Super Admin can see all gyms, Admin only sees their assigned gyms
        if ($user->hasRole('Super Admin')) {
            $gyms = MasterGym::select('id', 'name')->orderBy('name')->get();
        } else {
            $gymIds = GymAdmin::where('admin_id', $user->id)->pluck('gym_id');
            $gyms = MasterGym::whereIn('id', $gymIds)->select('id', 'name')->orderBy('name')->get();
        }

        return Inertia::render('ScanQRCode/Index', [
            'gyms' => $gyms,
        ]);
    }

    /**
     * Process the scanned QR code.
     */
    public function scan(Request $request)
    {
        $request->validate([
            'unique_id' => 'required|string',
            'gym_id' => 'required|integer|exists:master_gyms,id',
        ]);

        // 1. Find user by unique_id
        $user = User::where('unique_id', $request->unique_id)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan.',
            ], 404);
        }

        // 2. Check UserGym membership
        $userGym = UserGym::where('user_id', $user->id)
            ->where('gym_id', $request->gym_id)
            ->first();

        if (!$userGym) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak terdaftar di gym ini.',
            ], 403);
        }

        // 3. Check membership expiry
        if (Carbon::parse($userGym->membership_end_at)->lt(Carbon::now())) {
            return response()->json([
                'success' => false,
                'message' => 'Membership user telah berakhir pada ' . Carbon::parse($userGym->membership_end_at)->format('d M Y') . '.',
            ], 403);
        }

        // 4. Insert scan record
        $scan = UserGymScan::create([
            'user_id' => $user->id,
            'gym_id' => $request->gym_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Scan berhasil! Selamat datang, ' . $user->name . '.',
            'data' => [
                'user_name' => $user->name,
                'user_email' => $user->email,
                'gym_name' => MasterGym::find($request->gym_id)->name,
                'scanned_at' => $scan->created_at->format('d M Y H:i:s'),
            ],
        ]);
    }
}
