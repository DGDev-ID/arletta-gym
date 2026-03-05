<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\MembershipApiController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\SignatureController;
use App\Http\Controllers\Api\TrainerApiController;
use App\Http\Controllers\Api\GymController;
use App\Http\Controllers\Api\WaitlistController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (Landing Page)
|--------------------------------------------------------------------------
| All routes prefixed with /api automatically by Laravel
*/

// ── Auth ──────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/emergency-contact', [AuthController::class, 'updateEmergencyContact']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

// ── Public (no auth) ──────────────────────────────────────────
Route::get('/memberships', [MembershipApiController::class, 'index']);
Route::get('/memberships/{id}', [MembershipApiController::class, 'show']);

// Master gyms for landing select
Route::get('/gyms', [GymController::class, 'index']);

// ⚠️  Literal routes BEFORE wildcard {id} to avoid route conflicts
Route::get('/trainers', [TrainerApiController::class, 'index']);
Route::get('/trainers/{id}/schedules', [TrainerApiController::class, 'schedules']);
Route::get('/trainers/{id}', [TrainerApiController::class, 'show']);

Route::get('/schedules', [ScheduleController::class, 'index']);
Route::get('/classes', [ScheduleController::class, 'classes']);

// ── Authenticated (sanctum) ─────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    // Profile
    Route::put('/users/me', [ProfileController::class, 'update']);
    Route::post('/uploads', [ProfileController::class, 'upload']);
        // Role-specific profile endpoints for landing
        Route::get('/members/me', [AuthController::class, 'memberMe']);
        Route::get('/trainers/me', [AuthController::class, 'trainerMe']);

    // Bookings
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::post('/bookings/{id}/cancel', [BookingController::class, 'cancel']);
    Route::post('/bookings/{id}/reschedule', [BookingController::class, 'reschedule']);

    // Waitlist & Online class
    Route::post('/waitlist', [WaitlistController::class, 'store']);
    Route::post('/online-class/join', [WaitlistController::class, 'joinOnlineClass']);

    // Payments
    Route::post('/payments/create', [PaymentController::class, 'create']);

    // Signatures
    Route::post('/signatures', [SignatureController::class, 'store']);

    // Trainer-specific (⚠️ literal path "clients/all" BEFORE wildcard {id})
    Route::get('/trainers/clients/all', [TrainerApiController::class, 'allMembers']);
    Route::get('/trainers/{id}/clients', [TrainerApiController::class, 'clients']);
});

// ── Webhooks (no auth, signature verification inside) ──────
Route::post('/payments/webhook', [PaymentController::class, 'webhook']);
