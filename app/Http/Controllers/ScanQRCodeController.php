<?php

namespace App\Http\Controllers;

use App\Models\GymAdmin;
use App\Models\MasterGym;
use App\Models\User;
use App\Models\UserGym;
use App\Models\UserGymScan;
use App\Models\ActivityLog;
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

        // 3. Check membership freeze
        if ($userGym->freezed_at && $userGym->freezed_end_at) {
            return response()->json([
                'success' => false,
                'message' => 'Membership user sedang dibekukan (freeze) hingga ' . Carbon::parse($userGym->freezed_end_at)->format('d M Y') . '.',
            ], 400);
        }

        // 4. Check membership expiry
        if (Carbon::parse($userGym->membership_end_at)->lt(Carbon::now())) {
            return response()->json([
                'success' => false,
                'message' => 'Membership user telah berakhir pada ' . Carbon::parse($userGym->membership_end_at)->format('d M Y') . '.',
            ], 403);
        }

        // 5. Insert scan record
        $scan = UserGymScan::create([
            'user_id' => $user->id,
            'gym_id' => $request->gym_id,
        ]);

        // 6. Insert activity log
        ActivityLog::create([
            'user_id' => $user->id,
            'gym_id' => $request->gym_id,
            'activity' => 'scan_qr',
            'description' => 'Member melakukan scan QR di gym ' . MasterGym::find($request->gym_id)->name,
        ]);

        // 7. Reminder H-7
        $reminder = $userGym->membership_end_at <= now()->addDays(7);
        $reminderDay = $reminder ? (int) now()->diffInDays($userGym->membership_end_at, false) : null;

        // 8. Get scan history (activity logs)
        $activityLogs = ActivityLog::where('user_id', $user->id)
            ->where('gym_id', $request->gym_id)
            ->where('activity', 'scan_qr')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn ($log) => [
                'activity' => $log->activity,
                'description' => $log->description,
                'logged_at' => $log->created_at->format('d M Y H:i'),
            ]);

        // Determine membership status
        $membershipStatus = 'active';
        if ($userGym->freezed_at && $userGym->freezed_end_at) {
            $membershipStatus = 'freeze';
        } elseif (Carbon::parse($userGym->membership_end_at)->lt(Carbon::now())) {
            $membershipStatus = 'expired';
        }

        return response()->json([
            'success' => true,
            'message' => 'Scan berhasil! Selamat datang, ' . $user->name . '.',
            'data' => [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'user_role' => $user->getRoleNames()->first() ?? '-',
                'membership_status' => $membershipStatus,
                'gym_name' => MasterGym::find($request->gym_id)->name,
                'scanned_at' => $scan->created_at->format('d M Y H:i:s'),
                'is_reminder' => $reminder,
                'reminder_day' => $reminderDay,
                'activity_logs' => $activityLogs,
            ],
        ]);
    }

    /**
     * Get scanned members for a gym (all time, latest scan per member).
     */
    public function members(Request $request)
    {
        $request->validate([
            'gym_id' => 'required|integer|exists:master_gyms,id',
        ]);

        $search = $request->search;

        // Get latest scan per member (all time), filtered by search on joined user
        $query = UserGymScan::where('gym_id', $request->gym_id)
            ->with('user')
            ->orderByDesc('created_at');

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Deduplicate by user_id before paginating
        $allScans = $query->get()->unique('user_id')->values();

        $perPage = 10;
        $page = (int) ($request->page ?? 1);
        $total = $allScans->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $pageItems = $allScans->forPage($page, $perPage);

        $members = $pageItems->map(function ($scan) use ($request) {
            $user = $scan->user;
            $userGym = UserGym::where('user_id', $user->id)
                ->where('gym_id', $request->gym_id)
                ->first();

            $membershipStatus = 'active';
            if ($userGym && $userGym->freezed_at && $userGym->freezed_end_at) {
                $membershipStatus = 'freeze';
            } elseif ($userGym && Carbon::parse($userGym->membership_end_at)->lt(Carbon::now())) {
                $membershipStatus = 'expired';
            }

            return [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'user_role' => $user->getRoleNames()->first() ?? '-',
                'membership_status' => $membershipStatus,
                'scanned_at' => $scan->created_at->format('d M Y H:i:s'),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $members,
            'pagination' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
            ],
        ]);
    }

    /**
     * Get member detail with full activity logs.
     */
    public function memberDetail(Request $request, int $userId)
    {
        $request->validate([
            'gym_id' => 'required|integer|exists:master_gyms,id',
        ]);

        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan.',
            ], 404);
        }

        $userGym = UserGym::where('user_id', $user->id)
            ->where('gym_id', $request->gym_id)
            ->first();

        $membershipStatus = 'active';
        $membershipEndAt = null;
        if ($userGym) {
            $membershipEndAt = Carbon::parse($userGym->membership_end_at)->format('d M Y');
            if ($userGym->freezed_at && $userGym->freezed_end_at) {
                $membershipStatus = 'freeze';
            } elseif (Carbon::parse($userGym->membership_end_at)->lt(Carbon::now())) {
                $membershipStatus = 'expired';
            }
        }

        $activityLogs = ActivityLog::where('user_id', $user->id)
            ->where('gym_id', $request->gym_id)
            ->where('activity', 'scan_qr')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($log) => [
                'activity' => $log->activity,
                'description' => $log->description,
                'logged_at' => $log->created_at->format('d M Y H:i'),
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'user_role' => $user->getRoleNames()->first() ?? '-',
                'membership_status' => $membershipStatus,
                'membership_end_at' => $membershipEndAt,
                'activity_logs' => $activityLogs,
            ],
        ]);
    }
}
