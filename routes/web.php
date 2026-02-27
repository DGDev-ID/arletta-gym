<?php

use App\Http\Controllers\Management\ManageAdminController;
use App\Http\Controllers\Management\ManagePersonalTrainerController;
use App\Http\Controllers\Management\ManageUserController;
use App\Http\Controllers\Master\MasterGymController;
use App\Http\Controllers\Master\MasterMembershipController;
use App\Http\Controllers\Master\MasterPtPackageController;
use App\Http\Controllers\Transaction\HistoryTransactionController;
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

        Route::resource('user', ManageUserController::class);
        Route::get('user/gym-details/{gym}', [ManageUserController::class, 'getGymDetails']);
        Route::post('user/check-promo', [ManageUserController::class, 'checkPromoCode']);
        Route::post('user/generate-payment', [ManageUserController::class, 'generatePayment'])->name('user.generate-payment');
        Route::post('user/generate-installment', [ManageUserController::class, 'generateInstallment'])->name('user.generate-installment');
        Route::post('/user/transactions/{transaction}/manual-action', [ManageUserController::class, 'approveOrRejectManualPayment'])
            ->name('management.user.transactions.manual-action');

        Route::resource('personal-trainer', ManagePersonalTrainerController::class);
    });

    Route::prefix('transaction')->name('transaction.')->group(function() {
        Route::resource('history', HistoryTransactionController::class);
    });
});

require __DIR__ . '/settings.php';
