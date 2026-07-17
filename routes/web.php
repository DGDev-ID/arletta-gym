<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Management\ManageAdminController;
use App\Http\Controllers\Management\ManageCheckInController;
use App\Http\Controllers\Management\ManagePersonalTrainerController;
use App\Http\Controllers\Management\ManageUserController;
use App\Http\Controllers\Management\ManageRescheduleController;
use App\Http\Controllers\Master\MasterClassScheduleController;
use App\Http\Controllers\Master\MasterGymClassController;
use App\Http\Controllers\Master\MasterGymController;
use App\Http\Controllers\Master\MasterMembershipController;
use App\Http\Controllers\Master\MasterProductController;
use App\Http\Controllers\Master\MasterPtPackageController;
use App\Http\Controllers\ScanQRCodeController;
use App\Http\Controllers\Transaction\HistoryTransactionController;
use App\Http\Controllers\Transaction\TransactionPerSessionController;
use App\Http\Controllers\Transaction\TransactionPosController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Laravel\Fortify\Features;

// Serve files from public storage (fallback when nginx symlink doesn't work)
Route::get('/storage/{path}', function (string $path) {
    if (!Storage::disk('public')->exists($path)) {
        abort(404);
    }
    return response()->file(Storage::disk('public')->path($path));
})->where('path', '.*')->name('storage.serve');

Route::get('/', function () {
    return redirect()->route('dashboard');
    // return Inertia::render('Welcome', [
    //     'canRegister' => Features::enabled(Features::registration()),
    // ]);
})->name('home');


Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard/export-csv', [DashboardController::class, 'exportCsv'])->name('dashboard.export-csv');

    Route::middleware(['role:Super Admin'])->group(function () {
        Route::prefix('master')->name('master.')->group(function () {
            Route::resource('gym', MasterGymController::class);
            Route::resource('membership', MasterMembershipController::class);
            Route::resource('personal-trainer-package', MasterPtPackageController::class);
            Route::resource('gym-class', MasterGymClassController::class);
            Route::resource('class-schedule', MasterClassScheduleController::class);
            Route::post('class-schedule/{classSchedule}/bookings', [MasterClassScheduleController::class, 'storeBooking'])->name('class-schedule.bookings.store');
            Route::delete('class-schedule/{classSchedule}/bookings/{booking}', [MasterClassScheduleController::class, 'destroyBooking'])->name('class-schedule.bookings.destroy');
        });
    });

    Route::middleware(['role:Super Admin|Admin'])->group(function () {
        Route::prefix('master')->name('master.')->group(function () {
            Route::resource('gym-class', MasterGymClassController::class);
            Route::resource('class-schedule', MasterClassScheduleController::class);
            Route::post('class-schedule/{classSchedule}/bookings', [MasterClassScheduleController::class, 'storeBooking'])->name('class-schedule.bookings.store');
            Route::delete('class-schedule/{classSchedule}/bookings/{booking}', [MasterClassScheduleController::class, 'destroyBooking'])->name('class-schedule.bookings.destroy');
            Route::resource('product', MasterProductController::class);
            Route::post('product/{product}/add-stock', [MasterProductController::class, 'addStock'])->name('product.add-stock');
            Route::delete('product/stock-log/{stockLog}', [MasterProductController::class, 'deleteStockLog'])->name('product.stock-log.delete');
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
            Route::post('user/freeze', [ManageUserController::class, 'freeze'])->name('user.freeze');
            Route::post('user/unfreeze', [ManageUserController::class, 'unfreeze'])->name('user.unfreeze');
            Route::post('user/update-transaction-freezing', [ManageUserController::class, 'updateTransactionFreezing'])->name('user.update-transaction-freezing');
            // Reschedule membership start (management)
            Route::get('reschedule', [ManageRescheduleController::class, 'index'])->name('user.reschedule.index');
            Route::post('reschedule', [ManageRescheduleController::class, 'update'])->name('user.reschedule.update');

            Route::resource('personal-trainer', ManagePersonalTrainerController::class);

            Route::get('check-in', [ManageCheckInController::class, 'index'])->name('check-in.index');
        });

        Route::prefix('transaction')->name('transaction.')->group(function () {
            Route::resource('history', HistoryTransactionController::class);
            Route::get('history-export-csv', [HistoryTransactionController::class, 'exportCsv'])->name('history.export-csv');
            Route::post('history/{id}/print-invoice', [HistoryTransactionController::class, 'printInvoice'])->name('history.print-invoice');
            Route::get('history/{id}/download-invoice', [HistoryTransactionController::class, 'downloadInvoice'])->name('history.download-invoice');
            Route::resource('transaction-per-session', TransactionPerSessionController::class);
            Route::get('pos', [TransactionPosController::class, 'index'])->name('pos.index');
            Route::get('pos/export-csv', [TransactionPosController::class, 'exportCsv'])->name('pos.export-csv');
            Route::post('pos', [TransactionPosController::class, 'store'])->name('pos.store');
            Route::post('pos/{transactionProductOut}/make-success', [TransactionPosController::class, 'makeSuccess'])->name('pos.make-success');
            Route::post('pos/{transactionProductOut}/make-failed', [TransactionPosController::class, 'makeFailed'])->name('pos.make-failed');
            Route::get('pos/{transactionProductOut}/invoice-pdf', [TransactionPosController::class, 'downloadInvoice'])->name('pos.invoice-pdf');
        });

        Route::get('scan-qr', [ScanQRCodeController::class, 'index'])->name('scan-qr.index');
        Route::post('scan-qr', [ScanQRCodeController::class, 'scan'])->name('scan-qr.scan');
        Route::get('scan-qr/members', [ScanQRCodeController::class, 'members'])->name('scan-qr.members');
        Route::get('scan-qr/member/{userId}', [ScanQRCodeController::class, 'memberDetail'])->name('scan-qr.member-detail');
    });
});

require __DIR__ . '/settings.php';
