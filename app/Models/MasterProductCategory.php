<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterProductCategory extends Model
{
    protected $fillable = [
        'name',
    ];

    // Relations
    public function products()
    {
        return $this->hasMany(MasterProduct::class, 'product_category_id');
    }
}
