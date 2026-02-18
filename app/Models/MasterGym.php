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
    ];

    // Relations
    public function gymPts()
    {
        return $this->hasMany(GymPt::class, 'gym_id');
    }

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

    public function gymAdmins()
    {
        return $this->hasMany(GymAdmin::class, 'gym_id');
    }

    public function memberships()
    {
        return $this->hasMany(MasterMembership::class, 'gym_id');
    }

    public function ptPackages()
    {
        return $this->hasMany(MasterPtPackage::class, 'gym_id');
    }

    
}
