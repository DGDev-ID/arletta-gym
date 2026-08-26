<?php

namespace App\Http\Services;

use App\Models\MasterBundlePackage;
use App\Models\MasterGym;
use App\Models\MasterMembership;
use App\Models\MasterPtPackage;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserPtPackage;
use App\Models\UserPtPackageInstalment;
use App\Models\UserPtPackageMember;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public static function processInstallment (UserPtPackageInstalment $userPtPackageInstallment, $paymentMethod, $userId) {
        // PPN disabled - harga sudah termasuk PPN
        // $midtransFee = self::calculatePaymentFee($paymentMethod, $userPtPackageInstallment->price + $userPtPackageInstallment->ppn_fee);
        $midtransFee = self::calculatePaymentFee($paymentMethod, $userPtPackageInstallment->price);

        $transaction = new Transaction([
            'user_id' => $userId,
            'method' => $paymentMethod,
            'transaction_type' => 'installment_pt',
            'full_pt_id' => $userPtPackageInstallment->userPtPackage->ptPackage->id,
            'installment_pt_id' => $userPtPackageInstallment->id,
            'price' => $userPtPackageInstallment->price,
            'ppn_fee' => 0, // PPN disabled - $userPtPackageInstallment->ppn_fee,
            'midtrans_fee' => $midtransFee,
            'description' => 'Installment pay',
            // PPN disabled - harga sudah termasuk PPN
            // 'total_price' => $userPtPackageInstallment->price + $userPtPackageInstallment->ppn_fee + $midtransFee,
            'total_price' => $userPtPackageInstallment->price + $midtransFee,
            'status' => 'pending'
        ]);
        $transaction->save();

        $transaction->transactionDetails()->create([
            'status' => 'pending',
        ]);

        return $transaction;
    }

    public static function processPayment(array $data)
    {
        $validated = self::validateRequest($data);

        // Route bundle ke handler tersendiri
        if ($validated['transaction_type'] === 'bundle') {
            return self::processBundlePayment($validated);
        }

        $user = User::role('User')->findOrFail($validated['user_id']);
        $gym = MasterGym::findOrFail($validated['gym_id']);

        // 1. Resolve Item & Relation
        $isMembership = $validated['transaction_type'] === 'membership';
        $relation = $isMembership ? 'membershipPromos' : 'ptPackagePromos';
        $itemClass = $isMembership ? MasterMembership::class : MasterPtPackage::class;
        $item = $itemClass::with($relation)->findOrFail($validated['type_id']);

        if ($item->gym_id != $gym->id) {
            throw ValidationException::withMessages(['type_id' => 'Item tidak ditemukan untuk gym yang dipilih.']);
        }

        // 2. Calculate Promos
        $currentPrice = $item->price;
        $bonusValue = 0;
        $appliedPromos = [];

        // Apply Global
        $item->{$relation}->whereNull('unique_code')->each(function ($promo) use (&$currentPrice, &$bonusValue, &$appliedPromos) {
            self::applyPromoLogic($promo, $currentPrice, $bonusValue, $appliedPromos, true);
        });

        // Apply Manual
        if (!empty($validated['promo_code'])) {
            $manualPromo = $item->{$relation}->whereNotNull('unique_code')->where('unique_code', $validated['promo_code'])->first();
            if ($manualPromo) {
                self::applyPromoLogic($manualPromo, $currentPrice, $bonusValue, $appliedPromos, false);
            }
        }

        // PPN disabled - harga sudah termasuk PPN
        // $ppnRate = 0.11;
        $ppnRate = 0;
        $netPrice = max(0, $currentPrice);
        $isDP = ($validated['payment_type'] === 'dp_payment' && !$isMembership);

        return DB::transaction(function () use ($validated, $user, $item, $isMembership, $isDP, $netPrice, $ppnRate, $appliedPromos, $bonusValue) {

            $paymentDetails = self::preparePaymentDetails($validated, $netPrice, $ppnRate, $isDP);
            $baseDuration = $isMembership ? $item->duration_in_days : $item->duration_in_sessions;
            $totalSessionsOrDays = $baseDuration + $bonusValue;

            $installmentPtId = null;
            $trxType = $isMembership ? 'membership' : ($isDP ? 'installment_pt' : 'full_pt');

            if (!$isMembership && $isDP) {
                if (!isset($validated['installment_pt_id'])) {
                    $userPtPackage = UserPtPackage::create([
                        'pt_package_id' => $item->id,
                        // 'pt_id' => !empty($validated['trainer_id']) ? $validated['trainer_id'] : null,
                        'sessions_remaining' => $totalSessionsOrDays,
                        'status' => 'instalment'
                    ]);

                    UserPtPackageMember::create([
                        'user_pt_package_id' => $userPtPackage->id,
                        'user_id' => $user->id
                    ]);

                    $dp = self::createInstallments($userPtPackage->id, $item->name, $paymentDetails);
                    $installmentPtId = $dp->id;
                } else {
                    $installmentPtId = $validated['installment_pt_id'];
                }
            }

            // 5. Create Transaction Record
            // VA dan QRIS tidak lagi melalui Midtrans payment gateway.
            // Menggunakan method 'debit' dan alur manual (pending → validasi admin).
            $trxMapping = [
                'manual' => ['m' => 'manual', 'd' => null],
                'va'     => ['m' => 'debit',   'd' => 'va'],
                'qris'   => ['m' => 'debit',   'd' => 'qris']
            ];
            $finalInstallmentId = ($trxType === 'installment_pt') ? $installmentPtId : null;
            $transaction = Transaction::create([
                'user_id' => $user->id,
                // 'trainer_id' => (!$isMembership && !empty($validated['trainer_id'])) ? $validated['trainer_id'] : null,
                'method' => $trxMapping[$validated['payment_method']]['m'],
                'method_midtrans_detail' => $trxMapping[$validated['payment_method']]['d'],
                'transaction_type' => $trxType,
                'membership_id' => $isMembership ? $item->id : null,
                'full_pt_id' => (!$isMembership) ? $item->id : null,
                'installment_pt_id' => (!$isMembership && $isDP) ? $finalInstallmentId : null,
                'price' => $paymentDetails['p1_base'],
                'midtrans_fee' => $paymentDetails['p1_fee'],
                'ppn_fee' => $paymentDetails['p1_ppn'],
                'total_price' => $paymentDetails['p1_total'],
                'status' => 'pending',
                'description' => ($isDP ? "[DP] " : "[FULL] ") . implode(", ", $appliedPromos),
                'sessions_or_days' => $totalSessionsOrDays
            ]);

                // store optional start_at on transaction (used when scheduling membership to start later)
                if (!empty($validated['start_at'])) {
                    $transaction->start_at = $validated['start_at'];
                    $transaction->save();
                }

            $transaction->transactionDetails()->create([
                'status' => 'pending',
            ]);

            return self::formatResponse($transaction, $isDP, $item->name, $appliedPromos, $bonusValue, $netPrice, $paymentDetails);
        });
    }

    /**
     * Proses pembayaran untuk tipe Bundle (Membership + Sesi PT dalam satu harga).
     * Mendukung promo kode (discount_percent / discount_amount).
     */
    public static function processBundlePayment(array $validated)
    {
        $user = User::role('User')->findOrFail($validated['user_id']);
        $gym  = MasterGym::findOrFail($validated['gym_id']);

        $bundle = MasterBundlePackage::with('bundlePackagePromos')->findOrFail($validated['type_id']);

        if ($bundle->gym_id != $gym->id) {
            throw ValidationException::withMessages(['type_id' => 'Bundle tidak ditemukan untuk gym yang dipilih.']);
        }

        $trxMapping = [
            'manual' => ['m' => 'manual', 'd' => null],
            'va'     => ['m' => 'debit',   'd' => 'va'],
            'qris'   => ['m' => 'debit',   'd' => 'qris'],
        ];

        return DB::transaction(function () use ($validated, $user, $bundle, $trxMapping) {
            $basePrice     = (float) $bundle->price;
            $totalDiscount = 0;
            $appliedPromos = [];

            // 1. Kumpulkan promo global (unique_code = null)
            $globals = $bundle->bundlePackagePromos->whereNull('unique_code');
            foreach ($globals as $promo) {
                if ($promo->type === 'discount_percent') {
                    $totalDiscount += $basePrice * ($promo->value / 100);
                } elseif ($promo->type === 'discount_amount') {
                    $totalDiscount += (float) $promo->value;
                }
                $appliedPromos[] = $promo;
            }

            // 2. Promo dari kode manual (unique_code tidak null)
            if (!empty($validated['promo_code'])) {
                $manualPromo = $bundle->bundlePackagePromos
                    ->whereNotNull('unique_code')
                    ->where('unique_code', strtoupper($validated['promo_code']))
                    ->first();

                if ($manualPromo) {
                    if ($manualPromo->type === 'discount_percent') {
                        $totalDiscount += $basePrice * ($manualPromo->value / 100);
                    } elseif ($manualPromo->type === 'discount_amount') {
                        $totalDiscount += (float) $manualPromo->value;
                    }
                    $appliedPromos[] = $manualPromo;
                }
            }

            $netPrice = max(0, $basePrice - $totalDiscount);
            $fee      = self::calculatePaymentFee($validated['payment_method'], $netPrice);

            $transaction = Transaction::create([
                'user_id'                => $user->id,
                'method'                 => $trxMapping[$validated['payment_method']]['m'],
                'method_midtrans_detail' => $trxMapping[$validated['payment_method']]['d'],
                'transaction_type'       => 'bundle',
                'bundle_package_id'      => $bundle->id,
                'price'                  => $netPrice,
                'midtrans_fee'           => $fee,
                'ppn_fee'                => 0,
                'total_price'            => $netPrice + $fee,
                'status'                 => 'pending',
                'description'            => "[BUNDLE] {$bundle->name}" . ($totalDiscount > 0 ? " (diskon Rp " . number_format($totalDiscount, 0, ',', '.') . ")" : ''),
                'sessions_or_days'       => $bundle->membership_duration_in_days,
            ]);

            if (!empty($validated['start_at'])) {
                $transaction->start_at = $validated['start_at'];
                $transaction->save();
            }

            $transaction->transactionDetails()->create(['status' => 'pending']);

            return [
                'transaction_id' => $transaction->id,
                'status'         => 'full',
                'summary' => [
                    'item_name'        => $bundle->name,
                    'membership'       => $bundle->membership_duration_in_days . ' hari',
                    'pt_sessions'      => $bundle->pt_sessions . ' sesi PT',
                    'total_net_price'  => $netPrice,
                    'total_discount'   => $totalDiscount,
                ],
                'payment_1' => [
                    'label' => 'Full Payment Bundle',
                    'base'  => $netPrice,
                    'ppn'   => 0,
                    'fee'   => $fee,
                    'total' => $netPrice + $fee,
                ],
            ];
        });
    }

    private static function preparePaymentDetails($validated, $netPrice, $ppnRate, $isDP)
    {
        if ($isDP) {
            $dpPercent = $validated['dp_percent'] / 100;
            $p1Base = $netPrice * $dpPercent;
            $p2Base = $netPrice * (1 - $dpPercent);

            $p1Ppn = $p1Base * $ppnRate;
            $p1Fee = self::calculatePaymentFee($validated['payment_method'], $p1Base + $p1Ppn);

            return [
                'p1_base' => $p1Base,
                'p1_ppn'  => $p1Ppn,
                'p1_fee'  => $p1Fee,
                'p1_total' => $p1Base + $p1Ppn + $p1Fee, // HITUNG DI SINI
                'p2_base' => $p2Base,
                'p2_ppn'  => $p2Base * $ppnRate,
                'is_dp'   => true,
                'dp_percent' => $validated['dp_percent']
            ];
        }

        $ppn = $netPrice * $ppnRate;
        $fee = self::calculatePaymentFee($validated['payment_method'], $netPrice + $ppn);

        return [
            'p1_base' => $netPrice,
            'p1_ppn'  => $ppn,
            'p1_fee'  => $fee,
            'p1_total' => $netPrice + $ppn + $fee, // HITUNG DI SINI
            'is_dp'   => false
        ];
    }

    private static function createInstallments($packageId, $itemName, &$details)
    {
        // P1 Total Calculation
        $details['p1_total'] = $details['p1_base'] + $details['p1_ppn'] + $details['p1_fee'];

        $dp = UserPtPackageInstalment::create([
            'user_pt_package_id' => $packageId,
            'description' => "DP Payment untuk $itemName",
            'price' => $details['p1_base'],
            'ppn_fee' => $details['p1_ppn'],
            'status' => 'unpaid',
            'must_paid_before' => now()->addDays(3)
        ]);

        UserPtPackageInstalment::create([
            'user_pt_package_id' => $packageId,
            'description' => "Pelunasan untuk $itemName",
            'price' => $details['p2_base'],
            'ppn_fee' => $details['p2_ppn'],
            'status' => 'unpaid',
            'must_paid_before' => now()->addDays(30)
        ]);

        return $dp;
    }

    private static function formatResponse(Transaction $transaction, $isDP, $itemName, $promos, $bonus, $net, $details)
    {
        $details['p1_total'] = $details['p1_base'] + $details['p1_ppn'] + $details['p1_fee'];

        $res = [
            'transaction_id' => $transaction->id,
            'status' => $isDP ? 'dp' : 'full',
            'summary' => [
                'item_name' => $itemName,
                'applied_promos' => $promos,
                'bonuses' => $bonus,
                'total_net_price' => $net,
            ],
            'payment_1' => [
                'label' => $isDP ? "Down Payment ({$details['dp_percent']}%)" : "Full Payment",
                'base' => $details['p1_base'],
                'ppn' => $details['p1_ppn'],
                'fee' => $details['p1_fee'],
                'total' => $details['p1_total']
            ]
        ];

        if ($isDP) {
            $res['payment_2'] = [
                'label' => "Sisa Pelunasan",
                'base' => $details['p2_base'],
                'ppn' => $details['p2_ppn'],
                'total_without_fee' => $details['p2_base'] + $details['p2_ppn']
            ];
        }

        return $res;
    }

    private static function calculatePaymentFee($method, $amountWithTax)
    {
        // Fee VA dan QRIS dihapus karena tidak lagi menggunakan payment gateway Midtrans.
        // Semua metode tidak dikenakan biaya layanan.
        return 0;
    }

    private static function applyPromoLogic($promo, &$price, &$bonuses, &$descriptions, $isGlobal)
    {
        $prefix = $isGlobal ? "[GLOBAL]" : "[{$promo->unique_code}]";
        switch ($promo->type) {
            case 'discount_percent':
                $discount = ($price * $promo->value) / 100;
                $price -= $discount;
                $descriptions[] = "$prefix Diskon {$promo->value}%";
                break;
            case 'discount_amount':
                $price -= $promo->value;
                $descriptions[] = "$prefix Diskon Rp " . number_format($promo->value, 0, ',', '.');
                break;
            case 'bonus_days':
            case 'bonus_sessions':
                $bonuses += $promo->value;
                $unit = str_contains($promo->type, 'days') ? 'Hari' : 'Sesi';
                $descriptions[] = "$prefix Bonus $promo->value $unit";
                break;
        }
    }

    protected static function validateRequest(array $data)
    {
        $validator = Validator::make($data, [
            'user_id' => ['required', 'exists:users,id', function ($attribute, $value, $fail) {
                if (!User::role('User')->where('id', $value)->exists()) {
                    $fail('User tidak ditemukan atau tidak memiliki akses sebagai member.');
                }
            }],
            'gym_id' => 'required|exists:master_gyms,id',
            'installment_pt_id' => ['nullable', 'exists:user_pt_package_instalments,id'],
            'transaction_type' => ['required', Rule::in(['membership', 'pt', 'bundle'])],
            'type_id' => 'required|integer',
            'payment_method' => ['required', Rule::in(['manual', 'va', 'qris'])],
            'start_at' => ['nullable', 'date'],
            'payment_type' => [
                'nullable',
                Rule::requiredIf(isset($data['transaction_type']) && $data['transaction_type'] === 'pt'),
                Rule::in(['full_payment', 'dp_payment'])
            ],
            'dp_percent' => [
                'nullable',
                Rule::requiredIf(isset($data['payment_type']) && $data['payment_type'] === 'dp_payment'),
                'numeric', 'min:0', 'max:100'
            ],
            'promo_code' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}
