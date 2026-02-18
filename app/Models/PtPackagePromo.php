<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PtPackagePromo extends Model
{
    protected $fillable = [
        'pt_package_id',
        'unique_code',
        'type',
        'value'
    ];

    // Relations
    public function ptPackage()
    {
        return $this->belongsTo(MasterPtPackage::class, 'pt_package_id');
    }
}
