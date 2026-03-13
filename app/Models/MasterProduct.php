<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterProduct extends Model
{
    protected $fillable = [
        'gym_id',
        'product_category_id',
        'name',
        'buy_price',
        'sell_price',
        'stock',
    ];

    protected $casts = [
        'buy_price' => 'decimal:2',
        'sell_price' => 'decimal:2',
        'stock' => 'integer',
    ];

    // Relations
    public function category()
    {
        return $this->belongsTo(MasterProductCategory::class, 'product_category_id');
    }

    public function gym()
    {
        return $this->belongsTo(MasterGym::class, 'gym_id');
    }

    public function transactionProducts()
    {
        return $this->hasMany(TransactionProduct::class, 'product_id');
    }
}
