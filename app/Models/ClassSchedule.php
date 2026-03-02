<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassSchedule extends Model
{
    protected $fillable = [
        'gym_class_id',
        'trainer_id',
        'date',
        'start_time',
        'end_time',
        'location',
        'capacity',
        'booked_count',
        'zoom_link',
        'is_cancelled',
        'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_cancelled' => 'boolean',
        ];
    }

    // Relations
    public function gymClass()
    {
        return $this->belongsTo(GymClass::class, 'gym_class_id');
    }

    public function trainer()
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'class_schedule_id');
    }

    public function waitlistEntries()
    {
        return $this->hasMany(WaitlistEntry::class, 'class_schedule_id');
    }

    // Accessors
    public function getIsFullAttribute(): bool
    {
        return $this->booked_count >= $this->capacity;
    }

    public function getAvailableSlotsAttribute(): int
    {
        return max(0, $this->capacity - $this->booked_count);
    }
}
