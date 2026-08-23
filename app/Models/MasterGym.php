<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterGym extends Model
{
    protected $fillable = [
        'name',
        'address',
        'address_coordinate',
        'description',
        'start_access',
        'price_per_session',
        'freeze_price',
    ];

    protected $casts = [
        'price_per_session' => 'decimal:2',
    ];

    // Relations
    public function ptDescriptions()
    {
        return $this->hasMany(PtDescription::class, 'gym_id');
    }

    public function ptImgUrls()
    {
        return $this->hasMany(PtImgUrl::class, 'gym_id');
    }

    public function gymImages()
    {
        return $this->hasMany(GymImage::class, 'gym_id');
    }

    public function admins()
    {
        return $this->belongsToMany(User::class, 'gym_admins', 'gym_id', 'admin_id');
    }

    public function personalTrainers()
    {
        return $this->belongsToMany(User::class, 'gym_pts', 'gym_id', 'pt_id');
    }

    public function gymPts()
    {
        return $this->hasMany(GymPt::class, 'gym_id');
    }

    public function memberships()
    {
        return $this->hasMany(MasterMembership::class, 'gym_id');
    }

    public function ptPackages()
    {
        return $this->hasMany(MasterPtPackage::class, 'gym_id');
    }

    public function bundlePackages()
    {
        return $this->hasMany(MasterBundlePackage::class, 'gym_id');
    }

    public function gymUsers()
    {
        return $this->belongsToMany(UserGym::class, 'gym_id', 'user_id');
    }

    public function perSessionTransactions()
    {
        return $this->hasMany(TransactionPerSession::class, 'gym_id');
    }
}
