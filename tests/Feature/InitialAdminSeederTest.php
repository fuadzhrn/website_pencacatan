<?php

namespace Tests\Feature;

use App\Models\User;
use App\UserRole;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InitialAdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_initial_admin_is_created_once_from_configuration(): void
    {
        config()->set('maintenance.initial_admin', [
            'name' => 'Administrator Utama',
            'email' => 'admin@example.com',
            'password' => 'Password123!',
        ]);

        $this->seed(AdminUserSeeder::class);

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->assertSame('Administrator Utama', $admin->name);
        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertTrue(Hash::check('Password123!', $admin->password));

        config()->set('maintenance.initial_admin.password', 'PasswordBaru123!');
        $this->seed(AdminUserSeeder::class);

        $this->assertDatabaseCount('users', 1);
        $this->assertTrue(Hash::check('Password123!', $admin->fresh()->password));
    }
}
