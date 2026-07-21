<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionProductOut extends Model
{
    protected $fillable = [
        'total_price',
        'status',
        'payment_method',
        'cash_paid',
        'cash_change',
        'created_by',
    ];

    protected $casts = [
        'total_price'  => 'decimal:2',
        'cash_paid'    => 'decimal:2',
        'cash_change'  => 'decimal:2',
    ];

    // Relations
    public function products()
    {
        return $this->hasMany(TransactionProduct::class, 'transaction_product_out_id');
    }
}
