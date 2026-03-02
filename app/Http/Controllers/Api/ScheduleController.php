<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\GymClass;
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
        path: '/api/classes',
        tags: ['Schedules'],
        summary: 'Get list of gym classes',
        parameters: [
            new OA\Parameter(name: 'gym_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Classes retrieved successfully'),
        ]
    )]
    public function classes(Request $request): JsonResponse
    {
        $query = GymClass::where('is_active', true);

        if ($request->filled('gym_id')) {
            $query->where('gym_id', $request->gym_id);
        }

        $classes = $query->with('gym:id,name')->get()->map(function ($class) {
            return [
                'id' => $class->id,
                'name' => $class->name,
                'description' => $class->description,
                'category' => $class->category,
                'default_capacity' => $class->default_capacity,
                'duration_minutes' => $class->duration_minutes,
                'image_url' => $class->image_url,
                'gym' => $class->gym ? [
                    'id' => $class->gym->id,
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
}
