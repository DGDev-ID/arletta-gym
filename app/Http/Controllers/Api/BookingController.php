<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\ClassSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Bookings', description: 'Class booking management')]
class BookingController extends Controller
{
    #[OA\Get(
        path: '/api/bookings',
        tags: ['Bookings'],
        summary: 'Get user\'s bookings',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['confirmed', 'cancelled', 'completed', 'no-show'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Bookings retrieved successfully'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Booking::where('user_id', $request->user()->id)
            ->with(['classSchedule.gymClass', 'classSchedule.trainer']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $bookings = $query->latest()->get()->map(function ($booking) {
            return [
                'id' => $booking->id,
                'booking_type' => $booking->booking_type,
                'status' => $booking->status,
                'notes' => $booking->notes,
                'created_at' => $booking->created_at,
                'schedule' => [
                    'id' => $booking->classSchedule->id,
                    'class_name' => $booking->classSchedule->gymClass->name,
                    'category' => $booking->classSchedule->gymClass->category,
                    'trainer_name' => $booking->classSchedule->trainer?->name,
                    'date' => $booking->classSchedule->date->toDateString(),
                    'start_time' => $booking->classSchedule->start_time,
                    'end_time' => $booking->classSchedule->end_time,
                    'location' => $booking->classSchedule->location,
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $bookings,
            'message' => 'Bookings retrieved successfully',
        ]);
    }

    #[OA\Post(
        path: '/api/bookings',
        tags: ['Bookings'],
        summary: 'Book a class',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['schedule_id'],
                properties: [
                    new OA\Property(property: 'schedule_id', type: 'integer'),
                    new OA\Property(property: 'booking_type', type: 'string', enum: ['in-person', 'online'], example: 'in-person'),
                    new OA\Property(property: 'notes', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Booking created'),
            new OA\Response(response: 409, description: 'Class is full or already booked'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'schedule_id' => 'required|exists:class_schedules,id',
            'booking_type' => 'nullable|in:in-person,online',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $schedule = ClassSchedule::findOrFail($validated['schedule_id']);

        // Check if already booked
        $existingBooking = Booking::where('user_id', $user->id)
            ->where('class_schedule_id', $schedule->id)
            ->whereIn('status', ['confirmed'])
            ->first();

        if ($existingBooking) {
            return response()->json([
                'success' => false,
                'message' => 'You have already booked this class.',
            ], 409);
        }

        // Check if cancelled
        if ($schedule->is_cancelled) {
            return response()->json([
                'success' => false,
                'message' => 'This class has been cancelled.',
            ], 409);
        }

        // Check capacity
        if ($schedule->is_full && ($validated['booking_type'] ?? 'in-person') === 'in-person') {
            return response()->json([
                'success' => false,
                'message' => 'Class is full. Consider joining online or the waitlist.',
                'data' => [
                    'is_full' => true,
                    'has_zoom' => !empty($schedule->zoom_link),
                ],
            ], 409);
        }

        $booking = DB::transaction(function () use ($validated, $user, $schedule) {
            $booking = Booking::create([
                'user_id' => $user->id,
                'class_schedule_id' => $schedule->id,
                'booking_type' => $validated['booking_type'] ?? 'in-person',
                'status' => 'confirmed',
                'notes' => $validated['notes'] ?? null,
            ]);

            // Only increment booked_count for in-person bookings
            if (($validated['booking_type'] ?? 'in-person') === 'in-person') {
                $schedule->increment('booked_count');
            }

            return $booking;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $booking->id,
                'booking_type' => $booking->booking_type,
                'status' => $booking->status,
            ],
            'message' => 'Class booked successfully.',
        ], 201);
    }

    #[OA\Post(
        path: '/api/bookings/{id}/cancel',
        tags: ['Bookings'],
        summary: 'Cancel a booking (double verification)',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['verification'],
                properties: [
                    new OA\Property(property: 'reason', type: 'string'),
                    new OA\Property(property: 'verification', type: 'string', example: 'CANCEL', description: 'Must type \'CANCEL\' to confirm'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Booking cancelled'),
            new OA\Response(response: 422, description: 'Verification failed'),
        ]
    )]
    public function cancel(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
            'verification' => 'required|string',
        ]);

        // Double verification: must type "CANCEL"
        if (strtoupper($validated['verification']) !== 'CANCEL') {
            return response()->json([
                'success' => false,
                'message' => 'Verification failed. Please type "CANCEL" to confirm.',
            ], 422);
        }

        $booking = Booking::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->where('status', 'confirmed')
            ->firstOrFail();

        DB::transaction(function () use ($booking, $validated) {
            $booking->update([
                'status' => 'cancelled',
                'cancel_reason' => $validated['reason'] ?? null,
                'cancel_verification' => $validated['verification'],
                'cancelled_at' => now(),
            ]);

            // Decrement booked count if in-person
            if ($booking->booking_type === 'in-person') {
                $booking->classSchedule->decrement('booked_count');
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Booking cancelled successfully.',
        ]);
    }

    #[OA\Post(
        path: '/api/bookings/{id}/reschedule',
        tags: ['Bookings'],
        summary: 'Reschedule a booking (PT or user)',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['new_schedule_id', 'reason'],
                properties: [
                    new OA\Property(property: 'new_schedule_id', type: 'integer'),
                    new OA\Property(property: 'reason', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Booking rescheduled'),
        ]
    )]
    public function reschedule(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'new_schedule_id' => 'required|exists:class_schedules,id',
            'reason' => 'required|string|max:500',
        ]);

        $user = $request->user();

        // PT can reschedule any booking they're training, users can only reschedule their own
        $bookingQuery = Booking::where('id', $id)->where('status', 'confirmed');

        if ($user->hasRole('Personal Trainer')) {
            $bookingQuery->whereHas('classSchedule', function ($q) use ($user) {
                $q->where('trainer_id', $user->id);
            });
        } else {
            $bookingQuery->where('user_id', $user->id);
        }

        $booking = $bookingQuery->firstOrFail();
        $newSchedule = ClassSchedule::findOrFail($validated['new_schedule_id']);

        if ($newSchedule->is_full) {
            return response()->json([
                'success' => false,
                'message' => 'The new schedule is full.',
            ], 409);
        }

        DB::transaction(function () use ($booking, $newSchedule, $validated) {
            $oldSchedule = $booking->classSchedule;

            // Decrement old schedule
            if ($booking->booking_type === 'in-person') {
                $oldSchedule->decrement('booked_count');
            }

            // Update booking
            $booking->update([
                'class_schedule_id' => $newSchedule->id,
                'notes' => 'Rescheduled: ' . $validated['reason'],
            ]);

            // Increment new schedule
            if ($booking->booking_type === 'in-person') {
                $newSchedule->increment('booked_count');
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Booking rescheduled successfully.',
        ]);
    }
}
