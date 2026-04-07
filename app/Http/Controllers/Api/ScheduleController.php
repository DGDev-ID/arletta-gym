<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\GymClass;
use App\Models\MasterGym;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Schedules', description: 'Class schedule endpoints')]
class ScheduleController extends Controller
{
    #[OA\Get(
        path: '/api/schedules',
        tags: ['Schedules'],
        summary: 'Get class schedules',
        parameters: [
            new OA\Parameter(name: 'date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'trainer_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'gym_class_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'gym_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Schedules retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = ClassSchedule::with(['gymClass', 'trainer.userDetail'])
            ->where('is_cancelled', false);

        // Exclude past schedules (date+time already passed)
        $query->where(function ($q) {
            $q->where('date', '>', now()->toDateString())
              ->orWhere(function ($q2) {
                  $q2->where('date', now()->toDateString())
                     ->where('end_time', '>', now()->format('H:i:s'));
              });
        });

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        if ($request->filled('trainer_id')) {
            $query->where('trainer_id', $request->trainer_id);
        }

        if ($request->filled('gym_class_id')) {
            $query->where('gym_class_id', $request->gym_class_id);
        }

        if ($request->filled('gym_id')) {
            $query->whereHas('gymClass', function ($q) use ($request) {
                $q->where('gym_id', $request->gym_id);
            });
        }

        $schedules = $query->orderBy('date')->orderBy('start_time')->get();

        $formatted = $schedules->map(function ($schedule) {
            return [
                'id' => $schedule->id,
                'class' => [
                    'id' => $schedule->gymClass->id,
                    'name' => $schedule->gymClass->name,
                    'category' => $schedule->gymClass->category,
                    'description' => $schedule->gymClass->description,
                    'image_url' => $schedule->gymClass->image_url,
                    'duration_minutes' => $schedule->gymClass->duration_minutes,
                ],
                'trainer' => $schedule->trainer ? [
                    'id' => $schedule->trainer->id,
                    'name' => $schedule->trainer->name,
                ] : null,
                'date' => $schedule->date->toDateString(),
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
                'location' => $schedule->location,
                'capacity' => $schedule->capacity,
                'booked_count' => $schedule->booked_count,
                'available_slots' => $schedule->available_slots,
                'is_full' => $schedule->is_full,
                'zoom_link' => $schedule->zoom_link ? true : false, // only indicate if available
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formatted,
            'message' => 'Schedules retrieved successfully',
        ]);
    }

    #[OA\Get(
        path: '/api/class-categories',
        tags: ['Schedules'],
        summary: 'Get distinct class categories for filter UI',
        parameters: [
            new OA\Parameter(name: 'gym_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Categories retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'string'), example: ['All', 'Cardio', 'Strength']),
                    ]
                )
            ),
        ]
    )]
    public function classCategories(Request $request): JsonResponse
    {
        $query = GymClass::where('is_active', true)->whereNotNull('category');

        if ($request->filled('gym_id')) {
            $query->where('gym_id', $request->gym_id);
        }

        $categories = $query->distinct()->pluck('category')->sort()->values();

        // Prepend 'All' as the first option so FE can use this directly
        $result = collect(['All'])->merge($categories)->values();

        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => 'Class categories retrieved successfully',
        ]);
    }

    #[OA\Get(
        path: '/api/classes',
        tags: ['Schedules'],
        summary: 'Get list of gym classes with enriched data for landing',
        parameters: [
            new OA\Parameter(name: 'gym_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'category', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Classes retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                    ]
                )
            ),
        ]
    )]
    public function classes(Request $request): JsonResponse
    {
        $query = GymClass::where('is_active', true);

        if ($request->filled('gym_id')) {
            $query->where('gym_id', $request->gym_id);
        }

        if ($request->filled('category') && $request->category !== 'All') {
            $query->where('category', $request->category);
        }

        $classes = $query->with([
            'gym:id,name',
            'schedules' => function ($q) {
                // Load only upcoming non-cancelled schedules for spot/trainer info
                $q->where('is_cancelled', false)
                  ->where(function ($q2) {
                      $q2->where('date', '>', now()->toDateString())
                         ->orWhere(function ($q3) {
                             $q3->where('date', now()->toDateString())
                                ->where('end_time', '>', now()->format('H:i:s'));
                         });
                  })
                  ->orderBy('date')
                  ->orderBy('start_time')
                  ->with('trainer:id,name')
                  ->limit(5);
            },
        ])->get()->map(function ($class) {
            // Take the nearest upcoming schedule for trainer / spot info
            $nextSchedule = $class->schedules->first();

            return [
                'id'              => $class->id,
                'name'            => $class->name,
                'description'     => $class->description,
                'category'        => $class->category,
                'level'           => $class->level,
                'benefits'        => $class->benefits ?? [],
                // Format like FE expects: "45 min"
                'duration'        => $class->duration_minutes . ' min',
                'duration_minutes'=> $class->duration_minutes,
                'image'           => $class->image_url,
                'image_url'       => $class->image_url,
                'trainer'         => $nextSchedule?->trainer?->name,
                'location'        => $nextSchedule?->location,
                'spotsLeft'       => $nextSchedule ? max(0, $nextSchedule->capacity - $nextSchedule->booked_count) : null,
                'totalSpots'      => $nextSchedule?->capacity ?? $class->default_capacity,
                'gym'             => $class->gym ? [
                    'id'   => $class->gym->id,
                    'name' => $class->gym->name,
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $classes,
            'message' => 'Classes retrieved successfully',
        ]);
    }

    #[OA\Post(
        path: '/api/trainers/{trainerId}/sessions',
        tags: ['Schedules'],
        summary: 'Create a PT session for a trainer',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'trainerId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'client_id', type: 'integer'),
                new OA\Property(property: 'gym_class_id', type: 'integer'),
                new OA\Property(property: 'date', type: 'string', format: 'date'),
                new OA\Property(property: 'start_time', type: 'string'),
                new OA\Property(property: 'end_time', type: 'string'),
                new OA\Property(property: 'location', type: 'string'),
                new OA\Property(property: 'capacity', type: 'integer'),
                new OA\Property(property: 'notes', type: 'string'),
            ])
        ),
        responses: [
            new OA\Response(response: 201, description: 'Session created'),
        ]
    )]
    public function store(Request $request, int $trainerId): JsonResponse
    {
        $user = $request->user();
        if (!$user || (!$user->hasRole(['Super Admin', 'Admin']) && $user->id !== $trainerId)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'client_id' => 'nullable|exists:users,id',
            'gym_class_id' => 'nullable|exists:gym_classes,id',
            'date' => 'required|date',
            'start_time' => 'required|string',
            'end_time' => 'required|string',
            'location' => 'nullable|string',
            'capacity' => 'nullable|integer',
            'notes' => 'nullable|string|max:500',
        ]);

        // Try to determine gym_class_id if not provided: prefer a class named 'Personal Training' in trainer's gyms
        $gymClassId = $validated['gym_class_id'] ?? null;
        if (!$gymClassId) {
            $trainerGyms = \App\Models\MasterGym::whereHas('gymPts', fn($q) => $q->where('user_id', $trainerId))->pluck('id');
            $found = \App\Models\GymClass::whereIn('gym_id', $trainerGyms)
                ->where(function ($q) {
                    $q->where('name', 'like', '%Personal%')
                      ->orWhere('category', 'like', '%pt%');
                })->first();

            $gymClassId = $found?->id;
        }

        // If still not found, pick any active class in trainer's first gym (best-effort)
        if (!$gymClassId) {
            $trainerGyms = \App\Models\MasterGym::whereHas('gymPts', fn($q) => $q->where('user_id', $trainerId))->pluck('id');
            $found = \App\Models\GymClass::whereIn('gym_id', $trainerGyms)->first();
            $gymClassId = $found?->id;
        }

        if (!$gymClassId) {
            return response()->json(['success' => false, 'message' => 'Unable to determine gym_class_id for session.'], 422);
        }

        $schedule = ClassSchedule::create([
            'gym_class_id' => $gymClassId,
            'trainer_id' => $trainerId,
            'date' => $validated['date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'location' => $validated['location'] ?? null,
            'capacity' => $validated['capacity'] ?? 1,
            'booked_count' => 0,
            'zoom_link' => null,
            'is_cancelled' => false,
        ]);

        // Optionally create a booking for client if client_id provided
        if (!empty($validated['client_id'])) {
            \App\Models\Booking::create([
                'user_id' => $validated['client_id'],
                'class_schedule_id' => $schedule->id,
                'booking_type' => 'in-person',
                'status' => 'confirmed',
                'notes' => $validated['notes'] ?? null,
            ]);
            // increment booked_count
            $schedule->increment('booked_count');
            $schedule->refresh();
        }

        return response()->json(['success' => true, 'data' => $schedule, 'message' => 'Session created successfully'], 201);
    }

    #[OA\Put(
        path: '/api/sessions/{id}',
        tags: ['Schedules'],
        summary: 'Update / reschedule a session',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(required: true,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'date', type: 'string', format: 'date'),
                new OA\Property(property: 'start_time', type: 'string'),
                new OA\Property(property: 'end_time', type: 'string'),
                new OA\Property(property: 'location', type: 'string'),
                new OA\Property(property: 'capacity', type: 'integer'),
                new OA\Property(property: 'notes', type: 'string'),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: 'Session updated'),
        ]
    )]
    public function update(Request $request, int $id): JsonResponse
    {
        $schedule = ClassSchedule::findOrFail($id);
        $user = $request->user();
        if (!$user || (!$user->hasRole(['Super Admin', 'Admin']) && $user->id !== $schedule->trainer_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'date' => 'nullable|date',
            'start_time' => 'nullable|string',
            'end_time' => 'nullable|string',
            'location' => 'nullable|string',
            'capacity' => 'nullable|integer',
            'notes' => 'nullable|string|max:500',
        ]);

        $schedule->update($validated);

        return response()->json(['success' => true, 'data' => $schedule, 'message' => 'Session updated successfully']);
    }

    #[OA\Post(
        path: '/api/sessions/{id}/cancel',
        tags: ['Schedules'],
        summary: 'Cancel a session',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(required: false,
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'cancel_reason', type: 'string'),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: 'Session cancelled'),
        ]
    )]
    public function cancel(Request $request, int $id): JsonResponse
    {
        $schedule = ClassSchedule::findOrFail($id);
        $user = $request->user();
        if (!$user || (!$user->hasRole(['Super Admin', 'Admin']) && $user->id !== $schedule->trainer_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'cancel_reason' => 'nullable|string|max:500',
        ]);

        $schedule->update([
            'is_cancelled' => true,
            'cancel_reason' => $validated['cancel_reason'] ?? null,
        ]);

        return response()->json(['success' => true, 'data' => $schedule, 'message' => 'Session cancelled successfully']);
    }

    #[OA\Delete(
        path: '/api/sessions/{id}',
        tags: ['Schedules'],
        summary: 'Delete a session',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Session deleted'),
        ]
    )]
    public function destroy(Request $request, int $id): JsonResponse
    {
        $schedule = ClassSchedule::findOrFail($id);
        $user = $request->user();
        if (!$user || (!$user->hasRole(['Super Admin', 'Admin']) && $user->id !== $schedule->trainer_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $schedule->delete();

        return response()->json(['success' => true, 'message' => 'Session deleted successfully']);
    }
}
