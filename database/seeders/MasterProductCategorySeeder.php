<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MasterProductCategory;

class MasterProductCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'makanan',
            'minuman',
            'lainnya',
        ];

        foreach ($categories as $name) {
            MasterProductCategory::updateOrCreate([
                'name' => $name,
            ], [
                'name' => $name,
            ]);
        }
    }
}
