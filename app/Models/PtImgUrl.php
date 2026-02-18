<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PtImgUrl extends Model
{
    protected $fillable = [
        'pt_id',
        'gym_id',
        'img_url',
    ];

    // Relations
    public function pt()
    {
        return $this->belongsTo(User::class, 'pt_id');
    }

    public function gym()
    {
        return $this->belongsTo(MasterGym::class, 'gym_id');
    }
}
