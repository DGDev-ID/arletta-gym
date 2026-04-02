<?php

namespace Database\Seeders;

use App\Models\WABlastTemplate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WABlastTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'template_id' => 'f3913bf3-fdd7-41dd-b034-42c46b6cb689',
                'template_name' => 'ACCOUNT_VERIFICATION'
            ]
        ];
        foreach ($data as $item) {
            WABlastTemplate::create($item);
        }
    }
}
