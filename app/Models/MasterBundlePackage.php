<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterBundlePackage extends Model
{
    protected $fillable = [
        'gym_id',
        'name',
        'description',
        'membership_duration_in_days',
        'pt_sessions',
        'price',
    ];

    // Relations
    public function gym()
    {
        return $this->belongsTo(MasterGym::class, 'gym_id');
    }

    public function bundlePackagePromos()
    {
        return $this->hasMany(BundlePackagePromo::class, 'bundle_package_id');
    }

    public function transactions()
    {
        return $this->hasMany(\App\Models\Transaction::class, 'bundle_package_id');
    }
}
