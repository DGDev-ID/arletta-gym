<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionDetail extends Model
{
    protected $fillable = [
        'transaction_id',
        'status',
        'description',
        'confirmed_by'
    ];

    // Relations
    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
