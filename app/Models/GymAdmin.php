<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GymAdmin extends Model
{
    protected $fillable = [
        'gym_id',
        'admin_id',
    ];

    // Relations
    public function gym()
    {
        return $this->belongsTo(MasterGym::class, 'gym_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
