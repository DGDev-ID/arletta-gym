<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ClassSchedule extends Model
{
    public const TYPE_SCHEDULE = 'schedule';
    public const TYPE_SESSION = 'session';
    protected $fillable = [
        'type',
        'gym_class_id',
        'trainer_id',
        'trainer_name',
        'date',
        'start_time',
        'end_time',
        'location',
        'capacity',
        'booked_count',
        'zoom_link',
        'is_cancelled',
        'cancel_reason',
        'is_recurring',
        'recurring_day_of_week',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_cancelled' => 'boolean',
            'is_recurring' => 'boolean',
            'recurring_day_of_week' => 'integer',
            'type' => 'string',
        ];
    }

    /**
     * Get the effective trainer name: from user relation or free-text override.
     */
    public function getEffectiveTrainerNameAttribute(): ?string
    {
        if ($this->trainer_id && $this->trainer) {
            return $this->trainer->name;
        }

        return $this->trainer_name;
    }

    /**
     * Advance date to the next occurrence of the recurring day and reset booked_count.
     */
    public function advanceToNextOccurrence(): void
    {
        if (! $this->is_recurring || $this->recurring_day_of_week === null) {
            return;
        }

        $next = now()->next(\Carbon\Carbon::getDays()[$this->recurring_day_of_week]);
        $this->update([
            'date' => $next->toDateString(),
            'booked_count' => 0,
        ]);
    }

    // Scopes
    public function scopeSessions($query)
    {
        return $query->where('type', self::TYPE_SESSION);
    }

    public function scopeSchedules($query)
    {
        return $query->where('type', self::TYPE_SCHEDULE);
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

    public function isSession(): bool
    {
        return $this->type === self::TYPE_SESSION;
    }

    public function isSchedule(): bool
    {
        return $this->type === self::TYPE_SCHEDULE;
    }
}
