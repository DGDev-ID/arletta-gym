<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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
        'photo',
        'emergency_name',
        'emergency_phone',
        'emergency_relation',
    ];

    protected $appends = ['photo_url'];

    protected function phoneNumber(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (!$value) return null;

                if (str_starts_with($value, '0')) {
                    return '62' . substr($value, 1);
                }

                return $value;
            }
        );
    }

    protected function photoUrl(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->photo ? asset('storage/' . $this->photo) : null,
        );
    }

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
