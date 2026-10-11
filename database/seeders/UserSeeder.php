<?php

namespace Database\Seeders;

use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            ['email' => 'admin@example.com', 'name' => 'Administrator Maintenance', 'role' => UserRole::Admin],
            ['email' => 'petugas@example.com', 'name' => 'Petugas Maintenance', 'role' => UserRole::Petugas],
            ['email' => 'supervisor@example.com', 'name' => 'Supervisor Maintenance', 'role' => UserRole::Supervisor],
        ] as $account) {
            User::query()->updateOrCreate(
                ['email' => $account['email']],
                ['name' => $account['name'], 'role' => $account['role'], 'status' => 'active', 'password' => 'password'],
            );
        }
    }
}
