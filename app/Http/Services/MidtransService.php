<?php

namespace App\Http\Services;

use Midtrans\Config;
use Midtrans\Snap;

class MidtransService
{
    public function __construct()
    {
        // Konfigurasi Midtrans dari file .env
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
    }

    public function getSnapToken($transaction, $user)
    {
        // Tentukan enabled_payments berdasarkan detail transaksi
        $enabledPayments = [];
        if ($transaction->method_midtrans_detail === 'va') {
            $enabledPayments = ['bank_transfer']; // Menampilkan semua Virtual Account yang aktif
        } elseif ($transaction->method_midtrans_detail === 'qris') {
            $enabledPayments = ['qris']; // Mengunci hanya ke QRIS
        }

        $params = [
            'transaction_details' => [
                'order_id' => $transaction->id . '-' . time(), // Gunakan ID transaksi unik
                'gross_amount' => (int) $transaction->total_price,
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email,
            ],
            'enabled_payments' => $enabledPayments,
            'item_details' => [
                [
                    'id' => $transaction->id,
                    'price' => (int) $transaction->total_price,
                    'quantity' => 1,
                    'name' => "Pembayaran " . $transaction->transaction_type,
                ]
            ]
        ];

        return Snap::getSnapToken($params);
    }
}