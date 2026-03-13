<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionPerSession extends Model
{
    protected $fillable = [
        'gym_id',
        'name',
        'phone_number',
        'price',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function gym(): BelongsTo
    {
        return $this->belongsTo(MasterGym::class, 'gym_id');
    }
}
