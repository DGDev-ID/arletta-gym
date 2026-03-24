<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\PtDescription;
use App\Models\MasterGym;

class ManagePersonalTrainerController extends Controller
{
    public function index(Request $request)
    {
        $query = User::role('Personal Trainer')->with('gymPts.gym');
        return Inertia::render('Management/PersonalTrainer/Index', [
            'personal_trainers' => $query->latest()->paginate(10)->withQueryString(),
        ]);
    }

    public function edit(User $personalTrainer)
    {
        $personalTrainer->load('gymPts.gym', 'ptDescriptions');

        $assignedGyms = $personalTrainer->gymPts->map(function ($gp) {
            return [
                'id' => $gp->gym->id,
                'name' => $gp->gym->name,
            ];
        })->toArray();

        $allGyms = MasterGym::select('id', 'name')->get()->toArray();

        $description = $personalTrainer->ptDescriptions->first()->description ?? null;

        return Inertia::render('Management/PersonalTrainer/Edit', [
            'personal_trainer' => [
                'id' => $personalTrainer->id,
                'name' => $personalTrainer->name,
                'description' => $description,
                'gyms' => $assignedGyms,
            ],
            'gyms' => $allGyms,
        ]);
    }

    public function update(Request $request, User $personalTrainer)
    {
        $validated = $request->validate([
            'gym_id' => 'required|exists:master_gyms,id',
            'description' => 'nullable|string',
        ]);

        PtDescription::updateOrCreate([
            'pt_id' => $personalTrainer->id,
            'gym_id' => $validated['gym_id'],
        ], [
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Deskripsi Personal Trainer berhasil disimpan.');
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
