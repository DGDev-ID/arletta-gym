<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'user_id',
        'guest_name',
        'guest_phone',
        'class_schedule_id',
        'booking_type',
        'status',
        'cancel_reason',
        'cancel_verification',
        'cancelled_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'cancelled_at' => 'datetime',
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

    /**
     * Display name: registered user name or guest name.
     */
    public function getParticipantNameAttribute(): string
    {
        return $this->user?->name ?? $this->guest_name ?? 'Guest';
    }
}
