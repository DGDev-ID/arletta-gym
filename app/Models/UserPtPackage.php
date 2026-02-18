<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPtPackage extends Model
{
    protected $fillable = [
        'pt_package_id',
        'pt_id',
        'sessions_remaining',
        'status'
    ];

    // Relations
    public function ptPackage()
    {
        return $this->belongsTo(MasterPtPackage::class, 'pt_package_id');
    }

    public function pt()
    {
        return $this->belongsTo(User::class, 'pt_id');
    }

    public function userPtPackageMembers()
    {
        return $this->hasMany(UserPtPackageMember::class, 'user_pt_package_id');
    }

    public function userPtPackageDetails()
    {
        return $this->hasMany(UserPtPackageDetail::class, 'user_pt_package_id');
    }

    public function userPtPackageInstalments()
    {
        return $this->hasMany(UserPtPackageInstalment::class, 'user_pt_package_id');
    }
}
