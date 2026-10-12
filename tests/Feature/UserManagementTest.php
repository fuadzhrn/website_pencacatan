<?php

namespace Tests\Feature;

use App\Models\Machine;
use App\Models\MaintenanceCategory;
use App\Models\MaintenanceCheck;
use App\Models\MaintenanceSchedule;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_database_users_on_the_desktop_index(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        User::factory()->create([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'role' => UserRole::Petugas,
            'status' => 'active',
        ]);
        User::factory()->create([
            'name' => 'Siti Rahma',
            'email' => 'siti@example.com',
            'role' => UserRole::Supervisor,
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($admin)->get('/users?search=BUDI&role=petugas&status=active');

        $response
            ->assertOk()
            ->assertViewIs('users.index')
            ->assertViewHas('users', fn ($users): bool => $users->count() === 1 && $users->first()->email === 'budi@example.com')
            ->assertSeeText('Budi Santoso')
            ->assertDontSeeText('Siti Rahma')
            ->assertSee('name="search"', false)
            ->assertSee('value="BUDI"', false);
    }

    public function test_admin_receives_database_users_and_real_forms_on_the_mobile_index(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $user = User::factory()->create([
            'name' => 'User Database Mobile',
            'email' => 'mobile@example.com',
            'phone' => '08123456789',
            'role' => UserRole::Supervisor,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get('/mobile/users');

        $response
            ->assertOk()
            ->assertViewIs('users.index')
            ->assertSeeText('User Database Mobile')
            ->assertSeeText('mobile@example.com')
            ->assertSee('action="'.url('/users').'"', false)
            ->assertSee('data-mobile-users-toggle-form', false)
            ->assertSee('data-mobile-users-reset-form', false)
            ->assertSee('data-mobile-users-delete-form', false)
            ->assertSee('data-user-id="'.$user->id.'"', false)
            ->assertDontSeeText('Rudi Hartono');
    }

    public function test_guest_is_redirected_from_user_creation_to_login(): void
    {
        $response = $this->get('/users/create');

        $response->assertRedirectToRoute('login');
    }

    /** @return array<string, array{UserRole}> */
    public static function nonAdminRoles(): array
    {
        return [
            'petugas' => [UserRole::Petugas],
            'supervisor' => [UserRole::Supervisor],
        ];
    }

    #[DataProvider('nonAdminRoles')]
    public function test_non_admin_cannot_create_a_user(UserRole $role): void
    {
        $user = User::factory()->create(['role' => $role]);

        $response = $this->actingAs($user)->post('/users', $this->validPayload());

        $response->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'new.user@example.com']);
    }

    public function test_admin_can_create_a_user_with_a_hashed_password(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->post('/users', $this->validPayload());

        $response
            ->assertRedirect('/users')
            ->assertSessionHas('success', 'User berhasil ditambahkan.');
        $createdUser = User::query()->where('email', 'new.user@example.com')->sole();
        $this->assertSame('User Baru', $createdUser->name);
        $this->assertSame(UserRole::Petugas, $createdUser->role);
        $this->assertSame('active', $createdUser->status);
        $this->assertTrue(Hash::check('rahasia', $createdUser->password));
        $this->assertNotSame('rahasia', $createdUser->password);
    }

    public function test_mobile_store_returns_to_the_mobile_user_index(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->post('/users', [
            ...$this->validPayload(),
            'context' => 'mobile',
        ]);

        $response
            ->assertRedirect('/mobile/users')
            ->assertSessionHas('success', 'User berhasil ditambahkan.');
    }

    public function test_store_rejects_invalid_profile_values(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->from('/users/create')->post('/users', [
            'name' => '',
            'email' => 'bukan-email',
            'password' => '12345',
            'password_confirmation' => 'berbeda',
            'role' => 'pemilik',
            'phone' => str_repeat('1', 31),
            'status' => 'blocked',
        ]);

        $response
            ->assertRedirect('/users/create')
            ->assertSessionHasErrors(['name', 'email', 'password', 'role', 'phone', 'status']);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_store_rejects_an_email_that_is_already_registered(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        User::factory()->create(['email' => 'new.user@example.com']);

        $response = $this->actingAs($admin)->post('/users', $this->validPayload());

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 2);
    }

    public function test_admin_can_open_create_show_and_edit_pages(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $user = User::factory()->create(['name' => 'Pengguna Detail']);

        $this->actingAs($admin)->get('/users/create')
            ->assertOk()
            ->assertViewIs('users.create')
            ->assertSeeText('Tambah User');
        $this->actingAs($admin)->get('/users/'.$user->id)
            ->assertOk()
            ->assertViewIs('users.show')
            ->assertSeeText('Pengguna Detail');
        $this->actingAs($admin)->get('/users/'.$user->id.'/edit')
            ->assertOk()
            ->assertViewIs('users.edit')
            ->assertSee('value="Pengguna Detail"', false);
    }

    public function test_admin_can_update_a_user_without_changing_the_password(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $user = User::factory()->create([
            'email' => 'before@example.com',
            'password' => 'password-lama',
            'role' => UserRole::Petugas,
            'status' => 'active',
        ]);
        $passwordBeforeUpdate = $user->password;

        $response = $this->actingAs($admin)->put('/users/'.$user->id, [
            'name' => 'Nama Diperbarui',
            'email' => 'after@example.com',
            'password' => '',
            'password_confirmation' => '',
            'role' => 'supervisor',
            'phone' => null,
            'status' => 'inactive',
        ]);

        $response
            ->assertRedirect('/users')
            ->assertSessionHas('success', 'User berhasil diperbarui.');
        $user->refresh();
        $this->assertSame('Nama Diperbarui', $user->name);
        $this->assertSame('after@example.com', $user->email);
        $this->assertSame(UserRole::Supervisor, $user->role);
        $this->assertSame('inactive', $user->status);
        $this->assertSame($passwordBeforeUpdate, $user->password);
    }

    public function test_update_allows_own_email_but_rejects_another_users_email(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $user = User::factory()->create(['email' => 'kept@example.com']);
        $otherUser = User::factory()->create(['email' => 'other@example.com']);
        $payload = [
            'name' => $user->name,
            'email' => 'kept@example.com',
            'role' => 'petugas',
            'phone' => null,
            'status' => 'active',
        ];

        $this->actingAs($admin)->put('/users/'.$user->id, $payload)
            ->assertSessionDoesntHaveErrors();
        $this->actingAs($admin)->put('/users/'.$user->id, [
            ...$payload,
            'email' => $otherUser->email,
        ])->assertSessionHasErrors('email');
        $this->assertSame('kept@example.com', $user->fresh()->email);
    }

    public function test_update_rejects_a_short_optional_password(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $user = User::factory()->create();

        $response = $this->actingAs($admin)->put('/users/'.$user->id, [
            'name' => $user->name,
            'email' => $user->email,
            'password' => '12345',
            'password_confirmation' => '12345',
            'role' => 'petugas',
            'phone' => null,
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_admin_cannot_deactivate_their_own_account_through_update(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'status' => 'active']);

        $response = $this->actingAs($admin)->put('/users/'.$admin->id, [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'admin',
            'phone' => null,
            'status' => 'inactive',
        ]);

        $response
            ->assertRedirect('/users')
            ->assertSessionHas('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
        $this->assertSame('active', $admin->fresh()->status);
    }

    public function test_admin_can_delete_another_user_without_maintenance_history(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $user = User::factory()->create();

        $response = $this->actingAs($admin)->delete('/users/'.$user->id);

        $response
            ->assertRedirect('/users')
            ->assertSessionHas('success', 'User berhasil dihapus.');
        $this->assertModelMissing($user);
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->delete('/users/'.$admin->id);

        $response
            ->assertRedirect('/users')
            ->assertSessionHas('error', 'Anda tidak dapat menghapus akun sendiri.');
        $this->assertModelExists($admin);
    }

    public function test_admin_cannot_delete_a_user_with_required_maintenance_history(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $user = User::factory()->create();
        $machine = Machine::query()->create([
            'equipment_name' => 'X-Ray History',
            'brand' => 'Smiths',
            'type' => 'HI-SCAN',
            'serial_number' => 'USER-HISTORY-001',
            'location' => 'Terminal',
            'airport' => 'Malikussaleh',
            'status' => 'active',
        ]);
        $category = MaintenanceCategory::query()->create([
            'name' => 'History Test',
            'frequency' => 'daily',
            'status' => 'active',
        ]);
        $schedule = MaintenanceSchedule::query()->create([
            'machine_id' => $machine->id,
            'maintenance_category_id' => $category->id,
            'scheduled_date' => '2026-10-12',
            'status' => 'completed',
        ]);
        MaintenanceCheck::query()->create([
            'maintenance_schedule_id' => $schedule->id,
            'machine_id' => $machine->id,
            'checked_by' => $user->id,
            'check_date' => '2026-10-12',
            'overall_status' => 'normal',
            'verification_status' => 'waiting',
        ]);

        $response = $this->actingAs($admin)->delete('/users/'.$user->id);

        $response
            ->assertRedirect('/users')
            ->assertSessionHas('error', 'User tidak dapat dihapus karena memiliki riwayat maintenance.');
        $this->assertModelExists($user);
    }

    public function test_admin_can_toggle_another_users_status(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $user = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($admin)->patch('/users/'.$user->id.'/toggle-status');

        $response
            ->assertRedirect('/users')
            ->assertSessionHas('success', 'Status user berhasil diubah.');
        $this->assertSame('inactive', $user->fresh()->status);
    }

    public function test_admin_cannot_deactivate_their_own_account(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'status' => 'active']);

        $response = $this->actingAs($admin)->patch('/users/'.$admin->id.'/toggle-status');

        $response
            ->assertRedirect('/users')
            ->assertSessionHas('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
        $this->assertSame('active', $admin->fresh()->status);
    }

    public function test_admin_can_reset_another_users_password_to_the_default(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $user = User::factory()->create(['password' => 'password-lama']);

        $response = $this->actingAs($admin)->patch('/users/'.$user->id.'/reset-password');

        $response
            ->assertRedirect('/users')
            ->assertSessionHas('success', 'Password user berhasil direset ke password default.');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_user_content_is_escaped_on_the_index(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        User::factory()->create(['name' => '<script>alert("xss")</script>']);

        $response = $this->actingAs($admin)->get('/users');

        $response
            ->assertOk()
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert("xss")</script>', false);
    }

    /** @return array<string, string|null> */
    private function validPayload(): array
    {
        return [
            'name' => 'User Baru',
            'email' => 'new.user@example.com',
            'password' => 'rahasia',
            'password_confirmation' => 'rahasia',
            'role' => 'petugas',
            'phone' => '081234567890',
            'status' => 'active',
        ];
    }
}
