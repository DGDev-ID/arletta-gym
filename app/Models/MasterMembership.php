<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterMembership extends Model
{
    protected $fillable = [
        'gym_id',
        'name',
        'description',
        'duration_in_days',
        'price'
    ];

    // Relations
    public function gym()
    {
        return $this->belongsTo(MasterGym::class, 'gym_id');
    }

    public function membershipPromos()
    {
        return $this->hasMany(MembershipPromo::class, 'membership_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'membership_id');
    }
}
