<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\GymAdmin;
use App\Models\MasterGym;
use App\Models\UserGym;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ManageCheckInController extends Controller
{
    public function index(Request $request)
    {
        $authUser = $request->user();

        // Determine gyms accessible by the logged-in user
        if ($authUser->hasRole('Super Admin')) {
            $gyms = MasterGym::select('id', 'name')->orderBy('name')->get();
            $gymIds = $gyms->pluck('id');
        } else {
            $gymIds = GymAdmin::where('admin_id', $authUser->id)->pluck('gym_id');
            $gyms = MasterGym::whereIn('id', $gymIds)->select('id', 'name')->orderBy('name')->get();
        }

        $query = ActivityLog::with(['user', 'gym'])
            ->whereIn('gym_id', $gymIds)
            ->where('activity', 'scan_qr')
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('gym_id')) {
            $query->where('gym_id', $request->gym_id);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', Carbon::parse($request->date));
        }

        $checkIns = $query->paginate(10)->withQueryString();

        // Attach membership_status to each item (batch-load UserGyms to avoid N+1)
        $items = $checkIns->getCollection();
        $pairs = $items->map(fn ($log) => $log->user_id . '_' . $log->gym_id)->unique();

        $userGyms = UserGym::whereIn(
            DB::raw("CONCAT(user_id,'_',gym_id)"),
            $pairs->values()->toArray()
        )->get()->keyBy(fn ($ug) => $ug->user_id . '_' . $ug->gym_id);

        $items->transform(function ($log) use ($userGyms) {
            $ug = $userGyms->get($log->user_id . '_' . $log->gym_id);
            $status = 'active';
            if ($ug) {
                if ($ug->freezed_at && $ug->freezed_end_at) {
                    $status = 'freeze';
                } elseif ($ug->membership_end_at && Carbon::parse($ug->membership_end_at)->lt(Carbon::now())) {
                    $status = 'expired';
                }
            }
            $log->membership_status = $status;
            $log->days_remaining = $ug && $ug->membership_end_at
                ? (int) Carbon::now()->startOfDay()->diffInDays(Carbon::parse($ug->membership_end_at)->startOfDay(), false)
                : null;
            return $log;
        });

        $checkIns->setCollection($items);

        return Inertia::render('Management/CheckIn/Index', [
            'checkIns' => $checkIns,
            'gyms'     => $gyms,
            'filters'  => $request->only(['search', 'gym_id', 'date']),
        ]);
    }
}
