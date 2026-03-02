<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\GymClass;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MasterClassScheduleController extends Controller
{
    public function index()
    {
        $schedules = ClassSchedule::with(['gymClass.gym', 'trainer'])
            ->latest('date')
            ->paginate(15);

        return Inertia::render('Master/ClassSchedule/Index', [
            'schedules' => $schedules,
        ]);
    }

    public function create()
    {
        return Inertia::render('Master/ClassSchedule/Create', [
            'gymClasses' => GymClass::where('is_active', true)->select('id', 'name', 'default_capacity', 'duration_minutes')->get(),
            'trainers' => User::role('Personal Trainer')->select('id', 'name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'gym_class_id' => 'required|exists:gym_classes,id',
            'trainer_id' => 'nullable|exists:users,id',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'location' => 'nullable|string|max:255',
            'capacity' => 'required|integer|min:1',
            'zoom_link' => 'nullable|url|max:500',
        ]);

        ClassSchedule::create($validated);

        return redirect()->route('master.class-schedule.index')->with('success', 'Jadwal kelas berhasil ditambahkan.');
    }

    public function edit(ClassSchedule $classSchedule)
    {
        $classSchedule->load(['gymClass', 'trainer']);

        return Inertia::render('Master/ClassSchedule/Edit', [
            'schedule' => $classSchedule,
            'gymClasses' => GymClass::where('is_active', true)->select('id', 'name', 'default_capacity', 'duration_minutes')->get(),
            'trainers' => User::role('Personal Trainer')->select('id', 'name')->get(),
        ]);
    }

    public function update(Request $request, ClassSchedule $classSchedule)
    {
        $validated = $request->validate([
            'gym_class_id' => 'required|exists:gym_classes,id',
            'trainer_id' => 'nullable|exists:users,id',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'location' => 'nullable|string|max:255',
            'capacity' => 'required|integer|min:1',
            'zoom_link' => 'nullable|url|max:500',
            'is_cancelled' => 'boolean',
            'cancel_reason' => 'nullable|string|required_if:is_cancelled,true',
        ]);

        $classSchedule->update($validated);

        return redirect()->route('master.class-schedule.index')->with('success', 'Jadwal kelas berhasil diperbarui.');
    }

    public function destroy(ClassSchedule $classSchedule)
    {
        $classSchedule->delete();

        return redirect()->route('master.class-schedule.index')->with('success', 'Jadwal kelas berhasil dihapus.');
    }
}
