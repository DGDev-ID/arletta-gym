<?php

namespace App\Http\Controllers\Master;

use App\Helpers\S3Helper;
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
            'level' => 'nullable|string|in:Beginner,Intermediate,Advanced,All Levels',
            'benefits' => 'nullable|string',
            'default_capacity' => 'required|integer|min:1',
            'duration_minutes' => 'required|integer|min:1',
            'image_url' => 'nullable|image|mimes:jpeg,png,jpg|max:10240',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('image_url')) {
            $file = $request->file('file');

            $tempFileName = S3Helper::storeFileTemp($file);
            $s3Path = S3Helper::storeFileToS3("gym-class", $tempFileName);
            $url = S3Helper::getUrlFileS3("gym-class", $tempFileName);

            S3Helper::removeFileTemp($tempFileName);
            $validated['image_url'] = $url;
        }

        // Parse benefits textarea (one per line) to JSON array
        if (isset($validated['benefits']) && is_string($validated['benefits'])) {
            $validated['benefits'] = array_values(array_filter(
                array_map('trim', explode("\n", $validated['benefits']))
            ));
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
            'level' => 'nullable|string|in:Beginner,Intermediate,Advanced,All Levels',
            'benefits' => 'nullable|string',
            'default_capacity' => 'required|integer|min:1',
            'duration_minutes' => 'required|integer|min:1',
            'image_url' => 'nullable|image|mimes:jpeg,png,jpg|max:10240',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('image_url')) {
            $file = $request->file('file');

            $tempFileName = S3Helper::storeFileTemp($file);
            $s3Path = S3Helper::storeFileToS3("gym-class", $tempFileName);
            $url = S3Helper::getUrlFileS3("gym-class", $tempFileName);

            S3Helper::removeFileTemp($tempFileName);
            $validated['image_url'] = $url;
        } else {
            unset($validated['image_url']);
        }

        // Parse benefits textarea (one per line) to JSON array
        if (isset($validated['benefits']) && is_string($validated['benefits'])) {
            $validated['benefits'] = array_values(array_filter(
                array_map('trim', explode("\n", $validated['benefits']))
            ));
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
