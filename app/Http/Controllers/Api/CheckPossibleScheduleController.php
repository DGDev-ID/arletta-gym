<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MasterMembership;
use App\Models\UserGym;
use Carbon\Carbon;

class CheckPossibleScheduleController extends Controller
{
    public function __invoke(Request $request, $membership_id)
    {
        $user = $request->user();

        $membership = MasterMembership::find($membership_id);
        if (!$membership) {
            return response()->json(['status' => false, 'message' => 'Membership not found'], 404);
        }

        $userGym = UserGym::where('user_id', $user->id)
            ->where('gym_id', $membership->gym_id)
            ->first();

        if (!$userGym) {
            return response()->json(['status' => false, 'membership_end_at' => null]);
        }

        if (!$userGym->membership_end_at) {
            return response()->json(['status' => false, 'membership_end_at' => null]);
        }

        $end = Carbon::parse($userGym->membership_end_at);
        if ($end->lessThanOrEqualTo(Carbon::today())) {
            return response()->json(['status' => false, 'membership_end_at' => $end->toDateTimeString()]);
        }

        return response()->json(['status' => true]);
    }
}
