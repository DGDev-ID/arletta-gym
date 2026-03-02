<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaitlistEntry extends Model
{
    protected $fillable = [
        'user_id',
        'class_schedule_id',
        'position',
        'status',
        'promoted_at',
    ];

    protected function casts(): array
    {
        return [
            'promoted_at' => 'datetime',
        ];
    }

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function classSchedule()
    {
        return $this->belongsTo(ClassSchedule::class, 'class_schedule_id');
    }
}
