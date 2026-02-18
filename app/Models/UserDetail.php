<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDetail extends Model
{
    protected $fillable = [
        'user_id',
        'nik',
        'birth_place',
        'birth_date',
        'gender',
        'address',
        'phone_number',
        'membership_end_at'
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
