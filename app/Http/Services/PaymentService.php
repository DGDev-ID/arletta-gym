<?php

namespace App\Http\Services;

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
    public static function processPayment(array $data)
    {
        $validated = self::validateRequest($data);
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

        // 3. Pricing Logic
        $ppnRate = 0.11;
        $netPrice = max(0, $currentPrice);
        $isDP = ($validated['payment_type'] === 'dp_payment' && !$isMembership);

        return DB::transaction(function () use ($validated, $user, $item, $isMembership, $isDP, $netPrice, $ppnRate, $appliedPromos, $bonusValue) {

            $paymentDetails = self::preparePaymentDetails($validated, $netPrice, $ppnRate, $isDP);

            $baseDuration = $isMembership ? $item->duration_in_days : $item->duration_in_sessions;
            $totalSessionsOrDays = $baseDuration + $bonusValue;

            // 4. Persistence Logic
            $ptPackageId = null;
            $trxType = $isMembership ? 'membership' : ($isDP ? 'installment_pt' : 'full_pt');

            if (!$isMembership) {
                // Create PT Package record
                $userPtPackage = UserPtPackage::create([
                    'pt_package_id' => $item->id,
                    'sessions_remaining' => $item->duration_in_sessions + $bonusValue,
                    'status' => $isDP ? 'installment' : 'active'
                ]);

                UserPtPackageMember::create([
                    'user_pt_package_id' => $userPtPackage->id,
                    'user_id' => $user->id
                ]);

                $ptPackageId = $userPtPackage->id;

                if ($isDP) {
                    self::createInstallments($userPtPackage->id, $item->name, $paymentDetails);
                }
            }

            // 5. Create Transaction Record
            $trxMapping = [
                'manual' => ['m' => 'manual', 'd' => null],
                'va'     => ['m' => 'midtrans', 'd' => 'va'],
                'qris'   => ['m' => 'midtrans', 'd' => 'qris']
            ];

            $transaction = Transaction::create([
                'user_id' => $user->id,
                'method' => $trxMapping[$validated['payment_method']]['m'],
                'method_midtrans_detail' => $trxMapping[$validated['payment_method']]['d'],
                'transaction_type' => $trxType,
                'membership_id' => $isMembership ? $item->id : null,
                'full_pt_id' => (!$isMembership && !$isDP) ? $item->id : null,
                'installment_pt_id' => $ptPackageId,
                'price' => $paymentDetails['p1_base'],
                'midtrans_fee' => $paymentDetails['p1_fee'],
                'ppn_fee' => $paymentDetails['p1_ppn'],
                'total_price' => $paymentDetails['p1_total'],
                'status' => 'pending',
                'description' => ($isDP ? "[DP] " : "[FULL] ") . implode(", ", $appliedPromos),
                'sessions_or_days' => $totalSessionsOrDays
            ]);

            return self::formatResponse($transaction, $isDP, $item->name, $appliedPromos, $bonusValue, $netPrice, $paymentDetails);
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

        UserPtPackageInstalment::create([
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
        return match ($method) {
            'qris' => $amountWithTax * 0.007,
            'va'   => 4000,
            default => 0,
        };
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
            'transaction_type' => ['required', Rule::in(['membership', 'pt'])],
            'type_id' => 'required|integer',
            'payment_method' => ['required', Rule::in(['manual', 'va', 'qris'])],
            'payment_type' => ['nullable', Rule::requiredIf($data['transaction_type'] === 'pt'), Rule::in(['full_payment', 'dp_payment'])],
            'dp_percent' => ['nullable', Rule::requiredIf(isset($data['payment_type']) && $data['payment_type'] === 'dp_payment'), 'numeric', 'min:0', 'max:100'],
            'promo_code' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}
