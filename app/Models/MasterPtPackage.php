<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterPtPackage extends Model
{
    protected $fillable = [
        'gym_id',
        'max_person',
        'duration_in_sessions',
        'price'
    ];

    // Relations
    public function gym()
    {
        return $this->belongsTo(MasterGym::class, 'gym_id');
    }

    public function ptPackagePromos()
    {
        return $this->hasMany(PtPackagePromo::class, 'pt_package_id');
    }

    public function userPtPackages()
    {
        return $this->hasMany(UserPtPackage::class, 'pt_package_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'full_pt_id');
    }
}
