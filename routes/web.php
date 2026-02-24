<?php

use App\Http\Controllers\Management\ManageAdminController;
use App\Http\Controllers\Management\ManageUserController;
use App\Http\Controllers\Master\MasterGymController;
use App\Http\Controllers\Master\MasterMembershipController;
use App\Http\Controllers\Master\MasterPtPackageController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return redirect()->route('dashboard');
    // return Inertia::render('Welcome', [
    //     'canRegister' => Features::enabled(Features::registration()),
    // ]);
})->name('home');


Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::prefix('master')->name('master.')->group(function () {
        Route::resource('gym', MasterGymController::class);
        Route::resource('membership', MasterMembershipController::class);
        Route::resource('personal-trainer-package', MasterPtPackageController::class);
    });

    Route::prefix('management')->name('management.')->group(function () {
        Route::resource('admin', ManageAdminController::class);
        Route::resource('user', ManageUserController::class)->only('index');
    });
});

require __DIR__ . '/settings.php';
