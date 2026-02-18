<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@arletta.com',
                'password' => bcrypt('password'),
                'roles' => ['Super Admin']
            ], 
            [
                'name' => 'Admin',
                'email' => 'admin@arletta.com',
                'password' => bcrypt('password'),
                'roles' => ['Admin']
            ]
        ];

        foreach ($users as $userData) {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => $userData['password'],
            ]);

            $user->assignRole($userData['roles']);
        }
    }
}
