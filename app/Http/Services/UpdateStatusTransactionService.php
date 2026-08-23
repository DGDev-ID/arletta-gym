<?php

namespace App\Http\Services;

use App\Jobs\SendWhatsappBlast;
use App\Models\MasterBundlePackage;
use App\Models\MasterMembership;
use App\Models\MasterPtPackage;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserGym;
use App\Models\UserPtPackage;
use App\Models\UserPtPackageInstalment;
use App\Models\WABlastTemplate;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateStatusTransactionService
{
    public static function makeSuccess(Transaction $transaction, $adminId = null, WhatsappBlastService $waBlastService)
    {
        DB::beginTransaction();

        try {
            if ($transaction->status == "failed") {
                throw new Exception("Transaksi memiliki status failed sebelumnya.");
            }

            $transaction->update(['status' => 'success']);

            if ($adminId) {
                $admin = User::role(['Admin', 'Super Admin'])->find($adminId);
                if (!$admin) throw new Exception("Admin / Super Admin tidak ditemukan!");
            }

            $transaction->transactionDetails()->create([
                'status' => 'success',
                'description' => $adminId ? 'Pembayaran disetujui oleh admin.' : 'Pembayaran sukses.',
                'confirmed_by' => $adminId
            ]);

            if ($transaction->transaction_type == "membership") {
                // Kirim Invoice
                try {
                    Log::info("Sending WA Blast for Transaction ID: {$transaction->unique_id}");
                    $waBlastTemplate = WABlastTemplate::where('template_name', 'INVOICE_MEMBERSHIP')->firstOrFail();
                    $userPhoneNumber = $transaction->user->userDetail->phone_number;

                    $totalPrice = $transaction->total_price;
                    $rupiahFormat = 'Rp ' . number_format((float)$totalPrice, 0, ',', '.');

                    SendWhatsappBlast::dispatch(
                        $userPhoneNumber,
                        $waBlastTemplate->template_id,
                        [
                            '{CUST_NAME}' => $transaction->user->name,
                            '{TRANSACTION_ID}' => $transaction->unique_id,
                            '{TRANSACTION_DATE}' => $transaction->updated_at->format('d M Y H:i'),
                            '{MEMBERSHIP_DAYS}' => $transaction->sessions_or_days,
                            '{TRANSACTION_TOTAL_PRICE}' => $rupiahFormat
                        ]
                    );
                } catch (\Exception $e) {
                    Log::error("Failed to send WA Blast for Transaction ID: {$transaction->unique_id}. Error: " . $e->getMessage());
                }


                $membership = MasterMembership::findOrFail($transaction->membership_id);
                $userGym = UserGym::where('gym_id', $membership->gym_id)
                    ->where('user_id', $transaction->user_id)->first();

                $today = Carbon::now();
                $startAccess = Carbon::parse($membership->gym->start_access);

                // If the transaction has an explicit start_at, prefer that when scheduling
                $specifiedStart = null;
                if (!empty($transaction->start_at)) {
                    $specifiedStart = Carbon::parse($transaction->start_at);
                }

                if (!$userGym) {
                    // New membership record
                    $startDate = $specifiedStart ?: ($today->lt($startAccess) ? $startAccess : $today);

                    UserGym::create([
                        'user_id' => $transaction->user_id,
                        'gym_id' => $membership->gym_id,
                        'membership_start_at' => $startDate,
                        'membership_end_at' => $startDate->copy()->addDays($transaction->sessions_or_days)
                    ]);
                } else {
                    $currentEnd = $userGym->membership_end_at ? Carbon::parse($userGym->membership_end_at) : null;

                    // Determine base date: if membership still active use current end, otherwise today
                    $baseDate = ($currentEnd && !$currentEnd->isPast()) ? $currentEnd : $today;

                    // If admin/user specified a start date for this transaction, use it as base
                    if ($specifiedStart) {
                        $baseDate = $specifiedStart;
                    }

                    if ($baseDate->lt($startAccess)) {
                        $baseDate = $startAccess;
                    }

                    if (!$currentEnd || $currentEnd->isPast()) {
                        // previous membership expired — set new start and end
                        $userGym->update([
                            'membership_start_at' => $baseDate,
                            'membership_end_at' => $baseDate->copy()->addDays($transaction->sessions_or_days)
                        ]);
                    } else {
                        // membership still active — extend end date only
                        $userGym->update([
                            'membership_end_at' => $baseDate->copy()->addDays($transaction->sessions_or_days)
                        ]);
                    }
                }
            }

            if ($transaction->transaction_type == "full_pt") {
                try {
                    Log::info("Sending WA Blast for Transaction ID: {$transaction->unique_id}");
                    $waBlastTemplate = WABlastTemplate::where('template_name', 'INVOICE_PERSONAL_TRAINER')->firstOrFail();
                    $userPhoneNumber = $transaction->user->userDetail->phone_number;

                    $totalPrice = $transaction->total_price;
                    $rupiahFormat = 'Rp ' . number_format((float)$totalPrice, 0, ',', '.');

                    SendWhatsappBlast::dispatch(
                        $userPhoneNumber,
                        $waBlastTemplate->template_id,
                        [
                            '{CUST_NAME}' => $transaction->user->name,
                            '{TRANSACTION_ID}' => $transaction->unique_id,
                            '{TRANSACTION_DATE}' => $transaction->updated_at->format('d M Y H:i'),
                            '{PAYMENT_TYPE}' => 'Full Payment',
                            '{TRX_DURATION}' => $transaction->sessions_or_days,
                            '{TRANSACTION_TOTAL_PRICE}' => $rupiahFormat
                        ]
                    );
                } catch (\Exception $e) {
                    Log::error("Failed to send WA Blast for Transaction ID: {$transaction->unique_id}. Error: " . $e->getMessage());
                }

                $ptPackage = MasterPtPackage::findOrFail($transaction->full_pt_id);
                $userPtPackage = UserPtPackage::create([
                    'pt_package_id' => $ptPackage->id,
                    // 'pt_id' => $transaction->trainer_id,
                    'sessions_remaining' => $transaction->sessions_or_days,
                    'status' => 'done_payment'
                ]);
                $userPtPackage->userPtPackageMembers()->create([
                    'user_id' => $transaction->user_id
                ]);
            }

            if ($transaction->transaction_type == "installment_pt") {
                try {
                    Log::info("Sending WA Blast for Transaction ID: {$transaction->unique_id}");
                    $waBlastTemplate = WABlastTemplate::where('template_name', 'INVOICE_PERSONAL_TRAINER')->firstOrFail();
                    $userPhoneNumber = $transaction->user->userDetail->phone_number;

                    $totalPrice = $transaction->total_price;
                    $rupiahFormat = 'Rp ' . number_format((float)$totalPrice, 0, ',', '.');

                    SendWhatsappBlast::dispatch(
                        $userPhoneNumber,
                        $waBlastTemplate->template_id,
                        [
                            '{CUST_NAME}' => $transaction->user->name,
                            '{TRANSACTION_ID}' => $transaction->unique_id,
                            '{TRANSACTION_DATE}' => $transaction->updated_at->format('d M Y H:i'),
                            '{PAYMENT_TYPE}' => 'Down Payment',
                            '{TRX_DURATION}' => $transaction->sessions_or_days,
                            '{TRANSACTION_TOTAL_PRICE}' => $rupiahFormat
                        ]
                    );
                } catch (\Exception $e) {
                    Log::error("Failed to send WA Blast for Transaction ID: {$transaction->unique_id}. Error: " . $e->getMessage());
                }

                $installment = UserPtPackageInstalment::findOrFail($transaction->installment_pt_id);
                $installment->update(['status' => 'paid']);

                $anyUnpaid = UserPtPackageInstalment::where('user_pt_package_id', $installment->user_pt_package_id)
                    ->where('status', 'unpaid')->exists();

                if (!$anyUnpaid) {
                    UserPtPackage::where('id', $installment->user_pt_package_id)
                        ->update(['status' => 'done_payment']);
                }
            }

            // --- Bundle: Aktifkan Membership + Buat Sesi PT ---
            if ($transaction->transaction_type == "bundle") {
                $bundle = MasterBundlePackage::findOrFail($transaction->bundle_package_id);

                // 1. Aktifkan / perpanjang membership (sama seperti alur membership biasa)
                $userGym = UserGym::where('gym_id', $bundle->gym_id)
                    ->where('user_id', $transaction->user_id)->first();

                $today = Carbon::now();
                $startAccess = Carbon::parse($bundle->gym->start_access);

                $specifiedStart = null;
                if (!empty($transaction->start_at)) {
                    $specifiedStart = Carbon::parse($transaction->start_at);
                }

                $membershipDays = $bundle->membership_duration_in_days;

                if (!$userGym) {
                    $startDate = $specifiedStart ?: ($today->lt($startAccess) ? $startAccess : $today);
                    UserGym::create([
                        'user_id'             => $transaction->user_id,
                        'gym_id'              => $bundle->gym_id,
                        'membership_start_at' => $startDate,
                        'membership_end_at'   => $startDate->copy()->addDays($membershipDays),
                    ]);
                } else {
                    $currentEnd = $userGym->membership_end_at ? Carbon::parse($userGym->membership_end_at) : null;
                    $baseDate = ($currentEnd && !$currentEnd->isPast()) ? $currentEnd : $today;

                    if ($specifiedStart) {
                        $baseDate = $specifiedStart;
                    }
                    if ($baseDate->lt($startAccess)) {
                        $baseDate = $startAccess;
                    }

                    if (!$currentEnd || $currentEnd->isPast()) {
                        $userGym->update([
                            'membership_start_at' => $baseDate,
                            'membership_end_at'   => $baseDate->copy()->addDays($membershipDays),
                        ]);
                    } else {
                        $userGym->update([
                            'membership_end_at' => $baseDate->copy()->addDays($membershipDays),
                        ]);
                    }
                }

                // 2. Buat UserPtPackage untuk sesi PT bonus dari bundle
                //    pt_package_id = null karena bundle bukan dari master_pt_packages
                $userPtPackage = UserPtPackage::create([
                    'pt_package_id'      => null,
                    'sessions_remaining' => $bundle->pt_sessions,
                    'status'             => 'done_payment',
                ]);
                $userPtPackage->userPtPackageMembers()->create([
                    'user_id' => $transaction->user_id,
                ]);

                Log::info("Bundle approved: user_id={$transaction->user_id}, bundle={$bundle->name}, membership={$membershipDays}d, pt_sessions={$bundle->pt_sessions}");
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public static function makeFailed(Transaction $transaction, $adminId = null)
    {
        $transaction->update([
            'status' => 'failed'
        ]);
        if ($adminId) {
            $user = User::role(['Admin', 'Super Admin'])->find($adminId);
            if (!$user) throw new Exception("Admin / Super Admin tidak ditemukan!");
        }

        $transaction->transactionDetails()->create([
            'status' => 'failed',
            'description' => $adminId ? 'Pembayaran ditolak oleh admin.' : 'Pembayaran gagal.',
            'confirmed_by' => $adminId ? $adminId : null
        ]);
    }
}
