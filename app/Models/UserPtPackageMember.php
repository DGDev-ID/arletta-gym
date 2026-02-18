<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPtPackageMember extends Model
{
    protected $fillable = [
        'user_pt_package_id',
        'user_id'
    ];

    // Relations
    public function userPtPackage()
    {
        return $this->belongsTo(UserPtPackage::class, 'user_pt_package_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
