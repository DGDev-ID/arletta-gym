<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HealthPolicyResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

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
            new OA\Response(response: 201, description: 'Registration successful',
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
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'string', 'confirmed', PasswordRule::min(8)],
            'phone_number' => 'nullable|string|max:20',
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

            // Create user detail if phone_number provided
            if (!empty($validated['phone_number'])) {
                $user->userDetail()->create([
                    'phone_number' => $validated['phone_number'],
                ]);
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

            return $user;
        });

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $this->formatUser($user),
                'token' => $token,
            ],
            'message' => 'Registration successful',
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
            new OA\Response(response: 200, description: 'Login successful',
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
            new OA\Response(response: 200, description: 'User profile retrieved',
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
        $user->load(['userDetail', 'roles']);

        // Get membership info
        $userGym = $user->userGyms()->with('gym')->first();
        $membershipInfo = null;

        if ($userGym) {
            $membershipInfo = [
                'gym_name' => $userGym->gym->name ?? null,
                'membership_end_at' => $userGym->membership_end_at,
                'is_active' => $userGym->membership_end_at && now()->lt($userGym->membership_end_at),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                ...$this->formatUser($user),
                'membership' => $membershipInfo,
                'qr_data' => [
                    'member_id' => $user->unique_id,
                    'name' => $user->name,
                    'start_date' => $userGym?->created_at?->toDateString(),
                    'duration' => $userGym ? now()->diffInDays($userGym->membership_end_at) . ' hari' : null,
                ],
            ],
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

        return [
            'id' => $user->id,
            'unique_id' => $user->unique_id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->roles->pluck('name')->first() ?? 'User',
            'phone_number' => $user->userDetail?->phone_number,
            'nik' => $user->userDetail?->nik,
            'birth_place' => $user->userDetail?->birth_place,
            'birth_date' => $user->userDetail?->birth_date,
            'gender' => $user->userDetail?->gender,
            'address' => $user->userDetail?->address,
            'created_at' => $user->created_at,
        ];
    }
}
