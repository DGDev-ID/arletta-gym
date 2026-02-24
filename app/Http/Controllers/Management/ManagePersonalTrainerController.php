<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ManagePersonalTrainerController extends Controller
{
    public function index(Request $request)
    {
        $query = User::role('Personal Trainer')->with('gymPts.gym');
        return Inertia::render('Management/PersonalTrainer/Index', [
            'personal_trainers' => $query->latest()->paginate(10)->withQueryString(),
        ]);
    }

    public function show(Request $request)
    {
        $search = $request->query('email');

        if (!$search) {
            return response()->json([]);
        }

        $users = User::withoutRole('Personal Trainer')
            ->where('email', 'LIKE', "%{$search}%")
            ->limit(5)
            ->get(['id', 'name', 'email']);

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $user = User::findOrFail($request->user_id);
        $user->assignRole('Personal Trainer');

        return redirect()->back()->with('success', "{$user->name} berhasil dijadikan Personal Trainer.");
    }

    public function destroy(User $personalTrainer)
    {
        $personalTrainer->removeRole('Personal Trainer');

        return redirect()->back()->with('success', "Akses Personal Trainer {$personalTrainer->name} telah dicabut.");
    }
}
