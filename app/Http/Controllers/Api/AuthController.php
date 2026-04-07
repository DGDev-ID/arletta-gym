<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Services\WhatsappBlastService as ServicesWhatsappBlastService;
use App\Models\HealthPolicyResponse;
use App\Models\User;
use App\Models\UserGym;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;
use App\Models\Booking;
use App\Models\ClassSchedule;
use App\Models\UserPtPackageMember;
use App\Models\UserPtPackageDetail;
use App\Models\WABlastTemplate;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;

#[OA\Tag(name: 'Auth', description: 'Authentication endpoints')]
class AuthController extends Controller
{
    #[OA\Post(
        path: '/api/auth/register',
        tags: ['Auth'],
        summary: 'Register a new user',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                    new OA\Property(property: 'password', type: 'string', example: 'password123'),
                    new OA\Property(property: 'password_confirmation', type: 'string', example: 'password123'),
                    new OA\Property(property: 'phone_number', type: 'string', example: '081234567890'),
                    new OA\Property(property: 'emergency_name', type: 'string', example: 'Nama Kontak Darurat'),
                    new OA\Property(property: 'emergency_phone', type: 'string', example: '081234567890'),
                    new OA\Property(property: 'emergency_relation', type: 'string', example: 'Orang tua'),
                    new OA\Property(property: 'health_policy', type: 'object', properties: [
                        new OA\Property(property: 'answers', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'question', type: 'string'),
                                new OA\Property(property: 'answer', type: 'string', enum: ['ya', 'tidak']),
                            ]
                        )),
                        new OA\Property(property: 'agreed_health_accuracy', type: 'boolean'),
                        new OA\Property(property: 'agreed_terms', type: 'boolean'),
                        new OA\Property(property: 'agreed_risk', type: 'boolean'),
                    ]),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Registration successful',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'user', type: 'object'),
                            new OA\Property(property: 'token', type: 'string'),
                        ]),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function register(Request $request, ServicesWhatsappBlastService $waService): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'string', 'confirmed', PasswordRule::min(8)],
            'phone_number' => 'nullable|string|max:20',
            'nik' => 'nullable|string',
            'birth_place' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'address' => 'nullable|string',
            'emergency_name' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:20',
            'emergency_relation' => 'nullable|string|max:100',
            'master_gym_id' => 'nullable|integer|exists:master_gyms,id',
            'health_policy' => 'nullable|array',
            'health_policy.answers' => 'nullable|array',
            'health_policy.agreed_health_accuracy' => 'nullable|boolean',
            'health_policy.agreed_terms' => 'nullable|boolean',
            'health_policy.agreed_risk' => 'nullable|boolean',
        ]);

        $user = DB::transaction(function () use ($validated, $request) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $user->assignRole('User');

            // Create user detail if any detail provided
            $detailFields = ['phone_number', 'nik', 'birth_place', 'birth_date', 'gender', 'address', 'emergency_name', 'emergency_phone', 'emergency_relation'];
            $detailData = array_filter(
                array_intersect_key($validated, array_flip($detailFields)),
                fn($v) => $v !== null
            );

            if (!empty($detailData)) {
                $user->userDetail()->create($detailData);
            }

            // Store health policy response
            if (!empty($validated['health_policy'])) {
                HealthPolicyResponse::create([
                    'user_id' => $user->id,
                    'answers' => $validated['health_policy']['answers'] ?? [],
                    'agreed_health_accuracy' => $validated['health_policy']['agreed_health_accuracy'] ?? false,
                    'agreed_terms' => $validated['health_policy']['agreed_terms'] ?? false,
                    'agreed_risk' => $validated['health_policy']['agreed_risk'] ?? false,
                    'ip_address' => $request->ip(),
                ]);
            }

            // Associate user with selected master gym (club)
            if (!empty($validated['master_gym_id'])) {
                UserGym::create([
                    'user_id' => $user->id,
                    'gym_id' => $validated['master_gym_id'],
                    'membership_end_at' => null,
                ]);
            }

            return $user;
        });

        // $token = $user->createToken('auth-token')->plainTextToken;
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        try {
            $waBlastTemplate = WABlastTemplate::where('template_name', 'ACCOUNT_VERIFICATION')->firstOrFail();
            $waService->send(
                $user->userDetail->phone_number,
                $waBlastTemplate->template_id,
                [
                    '{user}' => $user->name,
                    '{app_name}' => config('app.name'),
                    '{verification_link}' => $verificationUrl,
                ]
            );
        } catch (\Exception $e) {
            
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $this->formatUser($user),
                // 'token' => $token,
            ],
            'message' => 'Berhasil mendaftar. Silakan cek email Anda untuk verifikasi.',
        ], 201);
    }

    #[OA\Post(
        path: '/api/auth/login',
        tags: ['Auth'],
        summary: 'Login user',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                    new OA\Property(property: 'password', type: 'string', example: 'password123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login successful',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'user', type: 'object'),
                            new OA\Property(property: 'token', type: 'string'),
                        ]),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Invalid credentials'),
        ]
    )]
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        // Verify credentials without starting a web session
        // Auth::attempt() creates a session — not suitable for stateless API
        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Akun belum terverifikasi. Silakan cek WhatsApp Anda.',
            ], 403);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $this->formatUser($user),
                'token' => $token,
            ],
            'message' => 'Login successful',
        ]);
    }

    #[OA\Post(
        path: '/api/auth/logout',
        tags: ['Auth'],
        summary: 'Logout user',
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Logged out successfully'),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    #[OA\Get(
        path: '/api/auth/me',
        tags: ['Auth'],
        summary: 'Get authenticated user profile',
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User profile retrieved',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('userDetail', 'roles', 'gyms');

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    // Role-specific: member profile for landing
    #[OA\Get(
        path: '/api/members/me',
        tags: ['Auth'],
        summary: 'Get member-specific dashboard/profile for landing',
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Member profile retrieved',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function memberMe(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('userDetail');

        // Membership info (user_gyms)
        $userGym = \App\Models\UserGym::where('user_id', $user->id)->with('gym')->latest()->first();

        $membershipInfo = null;
        if ($userGym) {
            $membershipInfo = [
                'gym_id' => $userGym->gym_id,
                'gym_name' => $userGym->gym->name ?? null,
                'membership_end_at' => $userGym->membership_end_at,
                'is_active' => $userGym->membership_end_at && now()->lt($userGym->membership_end_at),
            ];
        }

        // Upcoming classes / bookings
        $bookings = Booking::where('user_id', $user->id)
            ->whereHas('classSchedule', function ($q) {
                $q->whereDate('date', '>=', now()->toDateString())->where('is_cancelled', false);
            })
            ->with(['classSchedule.gymClass', 'classSchedule.trainer'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($b) {
                return [
                    'id' => $b->id,
                    'name' => $b->classSchedule->gymClass->name ?? null,
                    'time' => $b->classSchedule->start_time ?? null,
                    'trainer' => $b->classSchedule->trainer?->name ?? null,
                    'date' => $b->classSchedule->date?->toDateString(),
                    'status' => $b->status,
                    'type' => 'class',
                ];
            });

        $response = [
            'member' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->userDetail?->phone_number,
                'memberSince' => $user->created_at?->toDateString(),
                'memberId' => $user->unique_id,
                'avatar' => $user->userDetail?->photo ?? null,
            ],
            'membership' => $membershipInfo,
            'upcomingClasses' => $bookings,
            'emergencyContact' => [
                'emergency_name' => $user->userDetail?->emergency_name,
                'emergency_phone' => $user->userDetail?->emergency_phone,
                'emergency_relation' => $user->userDetail?->emergency_relation,
            ],
        ];

        return response()->json(['success' => true, 'data' => $response]);
    }

    // Role-specific: trainer profile/dashboard for landing
    #[OA\Get(
        path: '/api/trainers/me',
        tags: ['Auth'],
        summary: 'Get trainer-specific dashboard/profile for landing',
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Trainer profile retrieved',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function trainerMe(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('userDetail');

        // Today's schedule
        $todaySchedules = ClassSchedule::where('trainer_id', $user->id)
            ->whereDate('date', now()->toDateString())
            ->where('is_cancelled', false)
            ->with(['bookings.user'])
            ->orderBy('start_time')
            ->get()
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'clientName' => $s->bookings->first()?->user?->name ?? null,
                    'time' => $s->start_time . ' - ' . $s->end_time,
                    'name' => $s->gymClass?->name ?? null,
                    'status' => 'scheduled',
                    'type' => 'pt-session',
                    'date' => $s->date?->toDateString(),
                    'location' => $s->location,
                ];
            });

        // Clients and stats
        $clientIds = UserPtPackageMember::whereHas('userPtPackage', function ($q) use ($user) {
            $q->where('pt_id', $user->id);
        })->pluck('user_id')->unique();

        $recentClients = \App\Models\User::whereIn('id', $clientIds->take(10))->get()->map(function ($c) {
            return [
                'name' => $c->name,
                'sessions' => null,
                'progress' => null,
                'avatar' => $c->userDetail?->photo ?? null,
            ];
        });

        $totalClients = $clientIds->count();

        $completedSessions = UserPtPackageDetail::whereHas('userPtPackage', function ($q) use ($user) {
            $q->where('pt_id', $user->id);
        })->sum('consumed_sessions');

        $response = [
            'trainer' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->userDetail?->phone_number,
                'joinedAt' => $user->created_at?->toDateString(),
                'specialty' => $user->userDetail?->specialty ?? null,
                'avatar' => $user->userDetail?->photo ?? null,
                'rating' => null,
                'totalClients' => $totalClients,
                'completedSessions' => $completedSessions,
                'memberId' => $user->unique_id,
            ],
            'todaySchedule' => $todaySchedules,
            'recentClients' => $recentClients,
        ];

        return response()->json(['success' => true, 'data' => $response]);
    }

    #[OA\Post(
        path: '/api/auth/emergency-contact',
        tags: ['Auth'],
        summary: 'Update emergency contact for authenticated user',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['emergency_name', 'emergency_phone', 'emergency_relation'],
                properties: [
                    new OA\Property(property: 'emergency_name', type: 'string'),
                    new OA\Property(property: 'emergency_phone', type: 'string'),
                    new OA\Property(property: 'emergency_relation', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Emergency contact updated'),
        ]
    )]
    public function updateEmergencyContact(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'emergency_name' => 'required|string|max:255',
            'emergency_phone' => 'required|string|max:20',
            'emergency_relation' => 'required|string|max:100',
        ]);

        $user->userDetail()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'emergency_name' => $validated['emergency_name'],
                'emergency_phone' => $validated['emergency_phone'],
                'emergency_relation' => $validated['emergency_relation'],
            ]
        );

        return response()->json([
            'success' => true,
            'data' => [
                'emergency_name' => $validated['emergency_name'],
                'emergency_phone' => $validated['emergency_phone'],
                'emergency_relation' => $validated['emergency_relation'],
            ],
            'message' => 'Emergency contact updated successfully',
        ]);
    }

    #[OA\Post(
        path: '/api/auth/forgot-password',
        tags: ['Auth'],
        summary: 'Send password reset link',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Reset link sent'),
        ]
    )]
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'success' => true,
                'message' => 'Password reset link sent to your email.',
            ]);
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }

    #[OA\Post(
        path: '/api/auth/reset-password',
        tags: ['Auth'],
        summary: 'Reset password with token',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['token', 'email', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'token', type: 'string'),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string'),
                    new OA\Property(property: 'password_confirmation', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Password reset successfully'),
        ]
    )]
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => ['required', 'string', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'success' => true,
                'message' => 'Password has been reset successfully.',
            ]);
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }

    private function formatUser(User $user): array
    {
        $user->load(['userDetail', 'roles']);

        // Prioritise 'Personal Trainer' over 'User' — a PT has both roles
        $roleNames = $user->roles->pluck('name');
        $role = $roleNames->contains('Personal Trainer') ? 'Personal Trainer' : ($roleNames->first() ?? 'User');

        return [
            'id' => $user->id,
            'unique_id' => $user->unique_id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $role,
            'roles' => $roleNames->values()->all(),
            'phone_number' => $user->userDetail?->phone_number,
            'nik' => $user->userDetail?->nik,
            'birth_place' => $user->userDetail?->birth_place,
            'birth_date' => $user->userDetail?->birth_date,
            'gender' => $user->userDetail?->gender,
            'emergency_name' => $user->userDetail?->emergency_name,
            'emergency_phone' => $user->userDetail?->emergency_phone,
            'emergency_relation' => $user->userDetail?->emergency_relation,
            'address' => $user->userDetail?->address,
            'photo' => $user->userDetail?->photo,
            'created_at' => $user->created_at,
        ];
    }
}
