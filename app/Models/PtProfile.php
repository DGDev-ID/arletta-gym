<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PtProfile extends Model
{
    protected $fillable = [
        'pt_id',
        'experience',
        'experience_years',
        'certifications',
        'specializations',
        'instagram',
        'rating',
    ];

    protected function casts(): array
    {
        return [
            'certifications' => 'array',
            'specializations' => 'array',
            'rating' => 'decimal:1',
        ];
    }

    public function pt()
    {
        return $this->belongsTo(User::class, 'pt_id');
    }
}
