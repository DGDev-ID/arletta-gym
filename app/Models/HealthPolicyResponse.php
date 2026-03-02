<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HealthPolicyResponse extends Model
{
    protected $fillable = [
        'user_id',
        'answers',
        'agreed_health_accuracy',
        'agreed_terms',
        'agreed_risk',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'agreed_health_accuracy' => 'boolean',
            'agreed_terms' => 'boolean',
            'agreed_risk' => 'boolean',
        ];
    }

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
