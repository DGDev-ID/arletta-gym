<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionProduct extends Model
{
    protected $fillable = [
        'product_id',
        'quantity',
        'type',
        'buy_price',
        'sell_price',
        'transaction_product_out_id',
    ];

    protected $casts = [
        'buy_price' => 'decimal:2',
        'sell_price' => 'decimal:2',
        'quantity' => 'integer',
    ];

    // Relations
    public function product()
    {
        return $this->belongsTo(MasterProduct::class, 'product_id');
    }

    public function transactionOut()
    {
        return $this->belongsTo(TransactionProductOut::class, 'transaction_product_out_id');
    }
}
