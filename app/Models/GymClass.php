<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GymClass extends Model
{
    protected $fillable = [
        'gym_id',
        'name',
        'description',
        'category',
        'default_capacity',
        'duration_minutes',
        'image_url',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // Relations
    public function gym()
    {
        return $this->belongsTo(MasterGym::class, 'gym_id');
    }

    public function schedules()
    {
        return $this->hasMany(ClassSchedule::class, 'gym_class_id');
    }
}
