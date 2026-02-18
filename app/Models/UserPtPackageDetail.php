<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPtPackageDetail extends Model
{
    protected $fillable = [
        'user_pt_package_id',
        'user_id',
        'started_at',
        'ended_at',
        'consumed_sessions',
        'is_confirmed'
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
