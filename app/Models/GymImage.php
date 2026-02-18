<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GymImage extends Model
{
    protected $fillable = [
        'gym_id',
        'img_url',
    ];

    // Relations
    public function gym()
    {
        return $this->belongsTo(MasterGym::class, 'gym_id');
    }
}
