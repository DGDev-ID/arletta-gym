<?php

use App\Http\Controllers\Master\MasterGymController;
use App\Http\Controllers\Master\MasterMembershipController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');


Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::prefix('master')->name('master.')->group(function () {
        Route::resource('gym', MasterGymController::class);
        Route::resource('membership', MasterMembershipController::class);
    });
});

require __DIR__ . '/settings.php';
