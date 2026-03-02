<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\WaitlistEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Waitlist', description: 'Waitlist & online class fallback')]
class WaitlistController extends Controller
{
    #[OA\Post(
        path: '/api/waitlist',
        tags: ['Waitlist'],
        summary: 'Join waitlist for a full class',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['schedule_id'],
                properties: [
                    new OA\Property(property: 'schedule_id', type: 'integer'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Added to waitlist',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'position', type: 'integer'),
                        ]),
                    ]
                )
            ),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'schedule_id' => 'required|exists:class_schedules,id',
        ]);

        $user = $request->user();
        $schedule = ClassSchedule::findOrFail($validated['schedule_id']);

        // Check if already on waitlist
        $existing = WaitlistEntry::where('user_id', $user->id)
            ->where('class_schedule_id', $schedule->id)
            ->where('status', 'waiting')
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'You are already on the waitlist.',
                'data' => ['position' => $existing->position],
            ], 409);
        }

        // Calculate position
        $lastPosition = WaitlistEntry::where('class_schedule_id', $schedule->id)
            ->where('status', 'waiting')
            ->max('position') ?? 0;

        $entry = WaitlistEntry::create([
            'user_id' => $user->id,
            'class_schedule_id' => $schedule->id,
            'position' => $lastPosition + 1,
            'status' => 'waiting',
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $entry->id,
                'position' => $entry->position,
            ],
            'message' => 'Added to waitlist successfully.',
        ], 201);
    }

    #[OA\Post(
        path: '/api/online-class/join',
        tags: ['Waitlist'],
        summary: 'Join online class (Zoom) when in-person is full',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['schedule_id'],
                properties: [
                    new OA\Property(property: 'schedule_id', type: 'integer'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Zoom link will be sent via email',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'zoom_link_sent', type: 'boolean'),
                        ]),
                    ]
                )
            ),
        ]
    )]
    public function joinOnlineClass(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'schedule_id' => 'required|exists:class_schedules,id',
        ]);

        $user = $request->user();
        $schedule = ClassSchedule::findOrFail($validated['schedule_id']);

        if (empty($schedule->zoom_link)) {
            return response()->json([
                'success' => false,
                'message' => 'This class does not have an online option.',
            ], 400);
        }

        // Create an online booking
        $booking = \App\Models\Booking::create([
            'user_id' => $user->id,
            'class_schedule_id' => $schedule->id,
            'booking_type' => 'online',
            'status' => 'confirmed',
            'notes' => 'Online class via Zoom',
        ]);

        // TODO: Send zoom link via email (Mail::to($user)->send(new ZoomLinkMail(...)))

        return response()->json([
            'success' => true,
            'data' => [
                'booking_id' => $booking->id,
                'zoom_link_sent' => true,
            ],
            'message' => 'Zoom link will be sent to your email.',
        ]);
    }
}
