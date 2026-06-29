<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionProductOut extends Model
{
    protected $fillable = [
        'total_price',
        'status',
        'payment_method',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
    ];

    // Relations
    public function products()
    {
        return $this->hasMany(TransactionProduct::class, 'transaction_product_out_id');
    }
}
