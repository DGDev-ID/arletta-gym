<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BundlePackagePromo extends Model
{
    protected $fillable = [
        'bundle_package_id',
        'unique_code',
        'type',
        'value',
    ];

    public function bundlePackage()
    {
        return $this->belongsTo(MasterBundlePackage::class, 'bundle_package_id');
    }
}
