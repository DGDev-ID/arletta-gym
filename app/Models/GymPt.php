<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GymPt extends Model
{
    protected $fillable = [
        'gym_id',
        'pt_id'
    ];

    // Relations
    public function gym()
    {
        return $this->belongsTo(MasterGym::class);
    }

    public function pt()
    {
        return $this->belongsTo(User::class, 'pt_id');
    }
}
