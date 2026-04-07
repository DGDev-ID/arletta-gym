<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\MasterPtPackage;
use App\Models\User;
use App\Models\UserPtPackageMember;
use App\Models\PtDescription;
use App\Models\PtProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Trainers', description: 'Personal trainer endpoints')]
class TrainerApiController extends Controller
{
    #[OA\Get(
        path: '/api/trainers/stats',
        tags: ['Trainers'],
        summary: 'Get aggregated trainer statistics for the landing page',
        responses: [
            new OA\Response(response: 200, description: 'Trainer stats retrieved'),
        ]
    )]
    public function stats(): JsonResponse
    {
        $trainerCount = User::role('Personal Trainer')->count();

        $combinedExperience = PtProfile::whereHas('pt', function ($q) {
            $q->role('Personal Trainer');
        })->whereNotNull('experience_years')->sum('experience_years');

        $happyClients = UserPtPackageMember::whereHas('userPtPackage', function ($q) {
            $q->whereHas('pt', function ($q2) {
                $q2->role('Personal Trainer');
            });
        })->distinct('user_id')->count('user_id');

        $avgRating = PtProfile::whereHas('pt', function ($q) {
            $q->role('Personal Trainer');
        })->whereNotNull('rating')->avg('rating');

        return response()->json([
            'success' => true,
            'data' => [
                ['value' => $trainerCount . '+', 'label' => 'Expert Trainers'],
                ['value' => ($combinedExperience > 0 ? $combinedExperience . '+' : '0'), 'label' => 'Years Combined Experience'],
                ['value' => ($happyClients > 0 ? $happyClients . '+' : '0'), 'label' => 'Happy Clients'],
                ['value' => $avgRating ? number_format((float) $avgRating, 1) : 'N/A', 'label' => 'Average Rating'],
            ],
            'message' => 'Trainer stats retrieved successfully',
        ]);
    }

    #[OA\Get(
        path: '/api/trainers',
        tags: ['Trainers'],
        summary: 'List all trainers',
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'gym_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Trainers retrieved'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = User::role('Personal Trainer')
            ->with(['userDetail', 'ptProfile', 'ptDescriptions', 'ptImgUrls', 'userDetail']);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('gym_id')) {
            $query->whereHas('gymPts', function ($q) use ($request) {
                $q->where('gym_id', $request->gym_id);
            });
        }

        $trainers = $query->latest()->get()->map(function ($trainer) {
            $clientCount = UserPtPackageMember::whereHas('userPtPackage', fn($q) =>
                $q->where('pt_id', $trainer->id)
            )->distinct('user_id')->count('user_id');

            return [
                'id' => $trainer->id,
                'name' => $trainer->name,
                'role' => 'Personal Trainer',
                'bio' => $trainer->ptDescriptions->first()->description ?? null,
                'image' => $trainer->ptImgUrls->first()?->img_url ?? null,
                'images' => $trainer->ptImgUrls->pluck('img_url')->toArray(),
                'phone_number' => $trainer->userDetail?->phone_number,
                'gender' => $trainer->userDetail?->gender,
                'experience' => $trainer->ptProfile?->experience ?? '',
                'certifications' => $trainer->ptProfile?->certifications ?? [],
                'specializations' => $trainer->ptProfile?->specializations ?? [],
                'instagram' => $trainer->ptProfile?->instagram ?? '',
                'rating' => $trainer->ptProfile?->rating ? (float) $trainer->ptProfile->rating : 0.0,
                'clients' => $clientCount,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $trainers,
            'message' => 'Trainer data retrieved successfully',
        ]);
    }

    #[OA\Get(
        path: '/api/trainers/{id}',
        tags: ['Trainers'],
        summary: 'Get trainer detail with packages',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Trainer detail retrieved'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $trainer = User::role('Personal Trainer')
            ->with(['userDetail', 'ptProfile', 'ptDescriptions', 'ptImgUrls', 'gymPts.gym', 'userDetail'])
            ->findOrFail($id);

        // Get PT packages from related gyms
        $gymIds = $trainer->gymPts->pluck('gym_id');
        $packages = MasterPtPackage::whereIn('gym_id', $gymIds)
            ->with(['ptPackagePromos' => function ($q) {
                $q->whereNull('unique_code');
            }])
            ->get()
            ->map(function ($pkg) {
                return [
                    'id' => $pkg->id,
                    'name' => $pkg->name,
                    'max_person' => $pkg->max_person,
                    'duration_in_sessions' => $pkg->duration_in_sessions,
                    'price' => $pkg->price,
                    'promos' => $pkg->ptPackagePromos->map(fn($p) => [
                        'type' => $p->type,
                        'value' => $p->value,
                    ]),
                ];
            });

        $clientCount = UserPtPackageMember::whereHas('userPtPackage', fn($q) =>
            $q->where('pt_id', $trainer->id)
        )->distinct('user_id')->count('user_id');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $trainer->id,
                'name' => $trainer->name,
                'role' => 'Personal Trainer',
                'bio' => $trainer->ptDescriptions->first()->description ?? null,
                'image' => $trainer->ptImgUrls->first()?->img_url ?? null,
                'images' => $trainer->ptImgUrls->pluck('img_url')->toArray(),
                'phone_number' => $trainer->userDetail?->phone_number,
                'gender' => $trainer->userDetail?->gender,
                'experience' => $trainer->ptProfile?->experience ?? '',
                'certifications' => $trainer->ptProfile?->certifications ?? [],
                'specializations' => $trainer->ptProfile?->specializations ?? [],
                'instagram' => $trainer->ptProfile?->instagram ?? '',
                'rating' => $trainer->ptProfile?->rating ? (float) $trainer->ptProfile->rating : 0.0,
                'clients' => $clientCount,
                'gyms' => $trainer->gymPts->map(fn($gp) => [
                    'id' => $gp->gym->id,
                    'name' => $gp->gym->name,
                ]),
                'packages' => $packages,
            ],
            'message' => 'Trainer detail retrieved successfully',
        ]);
    }

    #[OA\Get(
        path: '/api/trainers/{id}/clients',
        tags: ['Trainers'],
        summary: 'Get trainer\'s clients (PT only)',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Clients retrieved'),
        ]
    )]
    public function clients(Request $request, int $id): JsonResponse
    {
        $authUser = $request->user();

        // Only the trainer themselves or admin can view
        if (!$authUser->hasRole(['Super Admin', 'Admin']) && $authUser->id !== $id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $trainer = User::role('Personal Trainer')->findOrFail($id);

        // Get members from PT packages assigned to this trainer
        $clientIds = UserPtPackageMember::whereHas('userPtPackage', function ($q) use ($trainer) {
            $q->where('pt_id', $trainer->id);
        })->pluck('user_id')->unique();

        $clients = User::whereIn('id', $clientIds)
            ->with('userDetail')
            ->get()
            ->map(function ($client) use ($trainer) {
                $packageMembers = UserPtPackageMember::where('user_id', $client->id)
                    ->whereHas('userPtPackage', fn($q) => $q->where('pt_id', $trainer->id))
                    ->with('userPtPackage.ptPackage')
                    ->get();

                $totalSessions = $packageMembers->sum(fn($pm) => $pm->userPtPackage->sessions_remaining ?? 0);

                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'email' => $client->email,
                    'phone_number' => $client->userDetail?->phone_number,
                    'total_sessions_remaining' => $totalSessions,
                    'packages' => $packageMembers->map(fn($pm) => [
                        'package_name' => $pm->userPtPackage->ptPackage->name ?? 'N/A',
                        'sessions_remaining' => $pm->userPtPackage->sessions_remaining,
                        'status' => $pm->userPtPackage->status,
                    ]),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $clients,
            'message' => 'Client data retrieved successfully',
        ]);
    }

    #[OA\Get(
        path: '/api/trainers/clients/all',
        tags: ['Trainers'],
        summary: 'Get all gym members (PT views all, not just their clients)',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'All members retrieved'),
        ]
    )]
    public function allMembers(Request $request): JsonResponse
    {
        $authUser = $request->user();

        if (!$authUser->hasRole(['Super Admin', 'Admin', 'Personal Trainer'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $query = User::role('User')
            ->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'Personal Trainer');
            })
            ->with('userDetail');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        $members = $query->latest()->paginate(20);

        $members->getCollection()->transform(function ($member) {
            return [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'phone_number' => $member->userDetail?->phone_number,
                'gender' => $member->userDetail?->gender,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $members,
            'message' => 'All members retrieved successfully',
        ]);
    }

    #[OA\Get(
        path: '/api/trainers/{id}/schedules',
        tags: ['Trainers'],
        summary: 'Get trainer\'s class schedules',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Trainer schedules retrieved'),
        ]
    )]
    public function schedules(Request $request, int $id): JsonResponse
    {
        $trainer = User::role('Personal Trainer')->findOrFail($id);

        $query = \App\Models\ClassSchedule::where('trainer_id', $trainer->id)
            ->where('is_cancelled', false)
            ->with(['gymClass']);

        // Only load bookings if the requester is the trainer themselves (authenticated)
        $authUser = $request->user();
        $isOwner = $authUser && $authUser->id === $trainer->id;
        if ($isOwner) {
            $query->with('bookings.user');
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        $schedules = $query->orderBy('date')->orderBy('start_time')->get()->map(function ($schedule) use ($isOwner) {
            $data = [
                'id' => $schedule->id,
                'class_name' => $schedule->gymClass->name,
                'date' => $schedule->date->toDateString(),
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
                'location' => $schedule->location,
                'capacity' => $schedule->capacity,
                'booked_count' => $schedule->booked_count,
                'is_full' => $schedule->is_full,
            ];

            // Only expose booking details to the trainer themselves
            if ($isOwner) {
                $data['bookings'] = $schedule->bookings->map(fn($b) => [
                    'id' => $b->id,
                    'user_name' => $b->user->name,
                    'booking_type' => $b->booking_type,
                    'status' => $b->status,
                ]);
            }

            return $data;
        });

        return response()->json([
            'success' => true,
            'data' => $schedules,
            'message' => 'Trainer schedules retrieved successfully',
        ]);
    }

    public function updateDescription(Request $request, int $id): JsonResponse
    {
        $authUser = $request->user();

        if (!$authUser || !$authUser->hasRole(['Super Admin', 'Admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'gym_id' => 'required|exists:master_gyms,id',
            'description' => 'nullable|string',
        ]);

        PtDescription::updateOrCreate([
            'pt_id' => $id,
            'gym_id' => $validated['gym_id'],
        ], [
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json(['success' => true, 'message' => 'Description updated successfully']);
    }
}
