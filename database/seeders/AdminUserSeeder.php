<?php

namespace Database\Seeders;

use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $name = trim((string) config('maintenance.initial_admin.name'));
        $email = Str::lower(trim((string) config('maintenance.initial_admin.email')));
        $password = (string) config('maintenance.initial_admin.password');

        if ($name === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException('Nama dan email admin awal harus valid.');
        }

        if (Str::length($password) < 12) {
            throw new RuntimeException('Password admin awal minimal 12 karakter.');
        }

        User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'role' => UserRole::Admin,
                'email_verified_at' => now(),
            ],
        );
    }
}
