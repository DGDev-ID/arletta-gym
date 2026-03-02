<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\GymClass;
use App\Models\MasterGym;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MasterGymClassController extends Controller
{
    public function index()
    {
        $gymClasses = GymClass::with('gym')
            ->withCount(['schedules as class_schedules_count'])
            ->latest()
            ->paginate(10);

        return Inertia::render('Master/GymClass/Index', [
            'gymClasses' => $gymClasses,
        ]);
    }

    public function create()
    {
        return Inertia::render('Master/GymClass/Create', [
            'gyms' => MasterGym::select('id', 'name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'gym_id' => 'required|exists:master_gyms,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'default_capacity' => 'required|integer|min:1',
            'duration_minutes' => 'required|integer|min:1',
            'image_url' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('image_url')) {
            $validated['image_url'] = $request->file('image_url')->store('gym-classes', 'public');
        }

        GymClass::create($validated);

        return redirect()->route('master.gym-class.index')->with('success', 'Kelas gym berhasil ditambahkan.');
    }

    public function edit(GymClass $gymClass)
    {
        return Inertia::render('Master/GymClass/Edit', [
            'gymClass' => $gymClass,
            'gyms' => MasterGym::select('id', 'name')->get(),
        ]);
    }

    public function update(Request $request, GymClass $gymClass)
    {
        $validated = $request->validate([
            'gym_id' => 'required|exists:master_gyms,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'default_capacity' => 'required|integer|min:1',
            'duration_minutes' => 'required|integer|min:1',
            'image_url' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('image_url')) {
            $validated['image_url'] = $request->file('image_url')->store('gym-classes', 'public');
        } else {
            unset($validated['image_url']);
        }

        $gymClass->update($validated);

        return redirect()->route('master.gym-class.index')->with('success', 'Kelas gym berhasil diperbarui.');
    }

    public function destroy(GymClass $gymClass)
    {
        $gymClass->delete();

        return redirect()->route('master.gym-class.index')->with('success', 'Kelas gym berhasil dihapus.');
    }
}
