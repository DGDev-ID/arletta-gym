<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PtDescription extends Model
{
    protected $fillable = [
        'pt_id',
        'gym_id',
        'description',
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
