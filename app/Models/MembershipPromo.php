<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MembershipPromo extends Model
{
    protected $fillable = [
        'membership_id',
        'unique_code',
        'type',
        'value'
    ];

    // Relations
    public function membership()
    {
        return $this->belongsTo(MasterMembership::class, 'membership_id');
    }
}
