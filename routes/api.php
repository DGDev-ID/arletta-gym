<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Landing\TrainerController;
use App\Http\Controllers\Landing\MembershipController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Membership API
Route::get('/memberships', [MembershipController::class, 'index']);

// Trainer API
Route::get('/trainers', [TrainerController::class, 'index']);