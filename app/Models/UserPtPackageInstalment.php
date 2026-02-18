<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPtPackageInstalment extends Model
{
    protected $fillable = [
        'user_pt_package_id',
        'description',
        'price',
        'ppn_fee',
        'total_price',
        'status',
        'must_paid_before'
    ];

    // Relations
    public function userPtPackage()
    {
        return $this->belongsTo(UserPtPackage::class, 'user_pt_package_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'installment_pt_id');
    }
}
