<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Booking;
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
            'gym_class_id'          => 'required|exists:gym_classes,id',
            'trainer_id'            => 'nullable|exists:users,id',
            'trainer_name'          => 'nullable|string|max:255',
            'date'                  => 'required|date',
            'start_time'            => 'required|date_format:H:i',
            'end_time'              => 'required|date_format:H:i|after:start_time',
            'location'              => 'nullable|string|max:255',
            'capacity'              => 'required|integer|min:1',
            'zoom_link'             => 'nullable|url|max:500',
            'is_recurring'          => 'boolean',
            'recurring_day_of_week' => 'nullable|integer|min:0|max:6|required_if:is_recurring,true',
        ]);

        // If a user-based trainer is selected, clear the free-text name
        if (! empty($validated['trainer_id'])) {
            $validated['trainer_name'] = null;
        }

        ClassSchedule::create($validated);

        return redirect()->route('master.class-schedule.index')->with('success', 'Jadwal kelas berhasil ditambahkan.');
    }

    /**
     * Show bookings/participants for a specific schedule.
     */
    public function show(ClassSchedule $classSchedule)
    {
        $classSchedule->load(['gymClass.gym', 'trainer']);

        $bookings = Booking::where('class_schedule_id', $classSchedule->id)
            ->with('user:id,name,email')
            ->whereIn('status', ['confirmed', 'completed'])
            ->latest()
            ->get()
            ->map(fn ($b) => [
                'id'           => $b->id,
                'user_id'      => $b->user_id,
                'user_name'    => $b->user?->name,
                'user_email'   => $b->user?->email,
                'guest_name'   => $b->guest_name,
                'guest_phone'  => $b->guest_phone,
                'participant'  => $b->participant_name,
                'booking_type' => $b->booking_type,
                'status'       => $b->status,
                'notes'        => $b->notes,
                'created_at'   => $b->created_at,
            ]);

        return Inertia::render('Master/ClassSchedule/Show', [
            'schedule' => array_merge($classSchedule->toArray(), [
                'effective_trainer_name' => $classSchedule->effective_trainer_name,
            ]),
            'bookings' => $bookings,
            'users'    => User::select('id', 'name', 'email')->orderBy('name')->get(),
        ]);
    }

    /**
     * Add a participant (registered user or guest) to a schedule.
     */
    public function storeBooking(Request $request, ClassSchedule $classSchedule)
    {
        $validated = $request->validate([
            'user_id'      => 'nullable|exists:users,id',
            'guest_name'   => 'nullable|string|max:255',
            'guest_phone'  => 'nullable|string|max:50',
            'booking_type' => 'nullable|in:in-person,online',
            'notes'        => 'nullable|string|max:500',
        ]);

        if (empty($validated['user_id']) && empty($validated['guest_name'])) {
            return back()->withErrors(['user_id' => 'Pilih member atau isi nama tamu.']);
        }

        if (! empty($validated['user_id'])) {
            $exists = Booking::where('class_schedule_id', $classSchedule->id)
                ->where('user_id', $validated['user_id'])
                ->whereIn('status', ['confirmed'])
                ->exists();

            if ($exists) {
                return back()->withErrors(['user_id' => 'Member ini sudah terdaftar di jadwal ini.']);
            }

            $validated['guest_name'] = null;
            $validated['guest_phone'] = null;
        }

        Booking::create([
            'user_id'           => $validated['user_id'] ?? null,
            'guest_name'        => $validated['guest_name'] ?? null,
            'guest_phone'       => $validated['guest_phone'] ?? null,
            'class_schedule_id' => $classSchedule->id,
            'booking_type'      => $validated['booking_type'] ?? 'in-person',
            'status'            => 'confirmed',
            'notes'             => $validated['notes'] ?? null,
        ]);

        $classSchedule->increment('booked_count');

        return back()->with('success', 'Peserta berhasil ditambahkan.');
    }

    /**
     * Remove a participant from a schedule.
     */
    public function destroyBooking(ClassSchedule $classSchedule, Booking $booking)
    {
        if ($booking->class_schedule_id !== $classSchedule->id) {
            abort(404);
        }

        $booking->delete();
        $classSchedule->decrement('booked_count');

        return back()->with('success', 'Peserta berhasil dihapus.');
    }

    public function edit(ClassSchedule $classSchedule)
    {
        $classSchedule->load(['gymClass', 'trainer']);

        return Inertia::render('Master/ClassSchedule/Edit', [
            'schedule'   => $classSchedule,
            'gymClasses' => GymClass::where('is_active', true)->select('id', 'name', 'default_capacity', 'duration_minutes')->get(),
            'trainers'   => User::role('Personal Trainer')->select('id', 'name')->get(),
        ]);
    }

    public function update(Request $request, ClassSchedule $classSchedule)
    {
        $validated = $request->validate([
            'gym_class_id'          => 'required|exists:gym_classes,id',
            'trainer_id'            => 'nullable|exists:users,id',
            'trainer_name'          => 'nullable|string|max:255',
            'date'                  => 'required|date',
            'start_time'            => 'required|date_format:H:i',
            'end_time'              => 'required|date_format:H:i|after:start_time',
            'location'              => 'nullable|string|max:255',
            'capacity'              => 'required|integer|min:1',
            'zoom_link'             => 'nullable|url|max:500',
            'is_cancelled'          => 'boolean',
            'cancel_reason'         => 'nullable|string|required_if:is_cancelled,true',
            'is_recurring'          => 'boolean',
            'recurring_day_of_week' => 'nullable|integer|min:0|max:6|required_if:is_recurring,true',
        ]);

        if (! empty($validated['trainer_id'])) {
            $validated['trainer_name'] = null;
        }

        $classSchedule->update($validated);

        return redirect()->route('master.class-schedule.index')->with('success', 'Jadwal kelas berhasil diperbarui.');
    }

    public function destroy(ClassSchedule $classSchedule)
    {
        $classSchedule->delete();

        return redirect()->route('master.class-schedule.index')->with('success', 'Jadwal kelas berhasil dihapus.');
    }
}
