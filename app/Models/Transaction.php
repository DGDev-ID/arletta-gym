<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'unique_id',
        'method',
        'method_midtrans_detail',
        'transaction_type',
        'membership_id',
        'full_pt_id',
        'installment_pt_id',
        'price',
        'midtrans_fee',
        'ppn_fee',
        'total_price',
        'status',
        'description'
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function membership()
    {
        return $this->belongsTo(MasterMembership::class, 'membership_id');
    }

    public function fullPt()
    {
        return $this->belongsTo(MasterPtPackage::class, 'full_pt_id');
    }

    public function installmentPt()
    {
        return $this->belongsTo(UserPtPackageInstalment::class, 'installment_pt_id');
    }

    public function transactionDetails()
    {
        return $this->hasMany(TransactionDetail::class, 'transaction_id');
    }
}
