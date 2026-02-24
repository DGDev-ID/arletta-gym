<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\MasterGym;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class MasterGymController extends Controller
{
    public function index()
    {
        $gyms = MasterGym::withCount(['gymImages', 'admins', 'personalTrainers'])->latest()->paginate(10);

        return Inertia::render('Master/Gym/Index', [
            'gyms' => $gyms
        ]);
    }

    public function create()
    {
        return Inertia::render('Master/Gym/Create', [
            'users' => User::role('Admin')->select('id', 'name')->get(),
            'personalTrainers' => User::role('Personal Trainer')->select('id', 'name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'address_coordinate' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'start_access' => 'required|date',
            'admin_ids' => 'nullable|array',
            'admin_ids.*' => 'exists:users,id',
            'personal_trainer_ids' => 'nullable|array',
            'personal_trainer_ids.*' => 'exists:users,id',
        ]);

        $gym = MasterGym::create($validated);

        if (!empty($validated['admin_ids'])) {
            $gym->admins()->attach($validated['admin_ids']);
        }

        if (!empty($validated['personal_trainer_ids'])) {
            $gym->personalTrainers()->attach($validated['personal_trainer_ids']);
        }

        return redirect()->route('master.gym.index')->with('success', 'Gym berhasil ditambahkan.');
    }

    public function edit(MasterGym $gym)
    {
        $gym->load(['gymImages', 'admins', 'personalTrainers']);
        // return $gym;
        
        return Inertia::render('Master/Gym/Edit', [
            'gym' => $gym,
            'users' => User::role('Admin')->select('id', 'name')->get(),
            'personalTrainers' => User::role('Personal Trainer')->select('id', 'name')->get(),
        ]);
    }

    public function update(Request $request, MasterGym $gym)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'address_coordinate' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'start_access' => 'required|date',
            'admin_ids' => 'nullable|array',
            'admin_ids.*' => 'exists:users,id',
            'personal_trainer_ids' => 'nullable|array',
            'personal_trainer_ids.*' => 'exists:users,id',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'deleted_image_ids' => 'nullable|array', 
        ]);

        $gym->update($validated);

        if (isset($validated['admin_ids'])) {
            $gym->admins()->sync($validated['admin_ids']);
        } else {
            $gym->admins()->detach();
        }

        if (isset($validated['personal_trainer_ids'])) {
            $gym->personalTrainers()->sync($validated['personal_trainer_ids']);
        } else {
            $gym->personalTrainers()->detach();
        }

        if ($request->filled('deleted_image_ids')) {
            $imagesToDelete = $gym->gymImages()->whereIn('id', $request->deleted_image_ids)->get();
            foreach ($imagesToDelete as $image) {
                Storage::disk('public')->delete($image->img_url);
                $image->delete();
            }
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = $file->store('gym_images', 'public');
                $gym->gymImages()->create(['img_url' => $path]);
            }
        }

        return redirect()->route('master.gym.index')->with('success', 'Gym berhasil diperbarui.');
    }

    public function destroy(MasterGym $gym)
    {
        foreach ($gym->gymImages as $image) {
            Storage::disk('public')->delete($image->img_url);
        }

        $gym->admins()->detach();
        $gym->personalTrainers()->detach();
        
        $gym->delete();

        return redirect()->route('master.gym.index')->with('success', 'Gym berhasil dihapus.');
    }
}
