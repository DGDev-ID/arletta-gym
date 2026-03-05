<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Profile', description: 'User profile management')]
class ProfileController extends Controller
{
    #[OA\Put(
        path: '/api/users/me',
        tags: ['Profile'],
        summary: 'Update authenticated user\'s profile',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                    new OA\Property(property: 'phone_number', type: 'string', example: '081234567890'),
                    new OA\Property(property: 'nik', type: 'string'),
                    new OA\Property(property: 'birth_place', type: 'string'),
                    new OA\Property(property: 'birth_date', type: 'string', format: 'date'),
                    new OA\Property(property: 'gender', type: 'string', enum: ['male', 'female']),
                    new OA\Property(property: 'address', type: 'string'),
                    new OA\Property(property: 'emergency_name', type: 'string'),
                    new OA\Property(property: 'emergency_phone', type: 'string'),
                    new OA\Property(property: 'emergency_relation', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Profile updated successfully'),
        ]
    )]
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'password' => 'sometimes|string|min:8|confirmed',
            'phone_number' => 'nullable|string|max:20',
            'nik' => 'nullable|string',
            'birth_place' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'address' => 'nullable|string',
            'emergency_name' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:20',
            'emergency_relation' => 'nullable|string|max:100',
        ]);

        // Update user name
        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }
        if (isset($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();

        // Update or create user detail
        $detailFields = ['phone_number', 'nik', 'birth_place', 'birth_date', 'gender', 'address', 'emergency_name', 'emergency_phone', 'emergency_relation'];
        $detailData = array_filter(
            array_intersect_key($validated, array_flip($detailFields)),
            fn($v) => $v !== null
        );

        if (!empty($detailData)) {
            $user->userDetail()->updateOrCreate(
                ['user_id' => $user->id],
                $detailData
            );
        }

        $user->load('userDetail');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'unique_id' => $user->unique_id,
                'name' => $user->name,
                'email' => $user->email,
                'phone_number' => $user->userDetail?->phone_number,
                'nik' => $user->userDetail?->nik,
                'birth_place' => $user->userDetail?->birth_place,
                'birth_date' => $user->userDetail?->birth_date,
                'gender' => $user->userDetail?->gender,
                'address' => $user->userDetail?->address,
            ],
            'message' => 'Profile updated successfully',
        ]);
    }

    #[OA\Post(
        path: '/api/uploads',
        tags: ['Profile'],
        summary: 'Upload a file (image)',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'file', type: 'string', format: 'binary'),
                        new OA\Property(property: 'type', type: 'string', enum: ['avatar', 'document', 'other']),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'File uploaded successfully'),
        ]
    )]
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:5120|mimes:jpeg,png,jpg,gif,pdf',
            'type' => 'nullable|string|in:avatar,document,other',
        ]);

        $type = $request->input('type', 'other');
        $path = $request->file('file')->store("uploads/{$type}", 'public');

        return response()->json([
            'success' => true,
            'data' => [
                'url' => Storage::disk('public')->url($path),
                'path' => $path,
            ],
            'message' => 'File uploaded successfully',
        ]);
    }
}
