<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionFreezing extends Model
{
    protected $fillable = [
        'user_id',
        'gym_id',
        'total_price',
        'day_freeze',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function gym()
    {
        return $this->belongsTo(MasterGym::class, 'gym_id');
    }
}
