<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        return view('users.index', [
            ...$this->layoutData($request),
            ...$this->userListData($request),
        ]);
    }

    public function create(Request $request): View
    {
        return view('users.create', [
            ...$this->layoutData($request),
            ...$this->userListData($request),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        User::query()->create($request->safe()->except(['context', 'password_confirmation']));

        return $this->redirectToIndex($request)
            ->with('success', 'User berhasil ditambahkan.');
    }

    public function show(Request $request, User $user): View
    {
        return view('users.show', [
            ...$this->layoutData($request),
            ...$this->userListData($request),
            'managedUser' => $user,
        ]);
    }

    public function edit(Request $request, User $user): View
    {
        return view('users.edit', [
            ...$this->layoutData($request),
            ...$this->userListData($request),
            'managedUser' => $user,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user) && $request->string('status')->toString() === 'inactive') {
            return $this->redirectToIndex($request)
                ->with('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
        }

        $attributes = $request->safe()->except(['context', 'password_confirmation']);

        if ($attributes['password'] === null) {
            unset($attributes['password']);
        }

        $user->update($attributes);

        return $this->redirectToIndex($request)
            ->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return $this->redirectToIndex($request)
                ->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        if ($user->performedMaintenanceChecks()->exists() || $user->reportedMaintenanceFindings()->exists()) {
            return $this->redirectToIndex($request)
                ->with('error', 'User tidak dapat dihapus karena memiliki riwayat maintenance.');
        }

        $user->delete();

        return $this->redirectToIndex($request)
            ->with('success', 'User berhasil dihapus.');
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user) && $user->status === 'active') {
            return $this->redirectToIndex($request)
                ->with('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
        }

        $user->update([
            'status' => $user->status === 'active' ? 'inactive' : 'active',
        ]);

        return $this->redirectToIndex($request)
            ->with('success', 'Status user berhasil diubah.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $user->password = 'password';
        $user->setRememberToken(Str::random(60));
        $user->save();

        return $this->redirectToIndex($request)
            ->with('success', 'Password user berhasil direset ke password default.');
    }

    /** @return array{activeMenu: string, pageTitle: string, pageDescription: string, userName: string, userRole: string, userInitials: string} */
    private function layoutData(Request $request): array
    {
        /** @var User $authenticatedUser */
        $authenticatedUser = $request->user();
        $initials = Str::of($authenticatedUser->name)
            ->trim()
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $name): string => Str::upper(Str::substr($name, 0, 1)))
            ->implode('');

        return [
            'activeMenu' => 'user-management',
            'pageTitle' => 'User Management',
            'pageDescription' => 'Kelola akun pengguna dan hak akses Sistem Preventive Maintenance X-Ray.',
            'userName' => $authenticatedUser->name,
            'userRole' => $authenticatedUser->role->label(),
            'userInitials' => $initials,
        ];
    }

    /** @return array{users: mixed, summary: array{total: int, admin: int, petugas: int, supervisor: int}, filters: array{search: string, role: string, status: string}, roles: list<UserRole>} */
    private function userListData(Request $request): array
    {
        $search = $request->string('search')->trim()->toString();
        $role = $request->string('role')->toString();
        $status = $request->string('status')->toString();

        $users = User::query()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->when(in_array($role, $this->roleValues(), true), fn (Builder $query): Builder => $query->where('role', $role))
            ->when(in_array($status, ['active', 'inactive'], true), fn (Builder $query): Builder => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return [
            'users' => $users,
            'summary' => [
                'total' => User::query()->count(),
                'admin' => User::query()->where('role', UserRole::Admin)->count(),
                'petugas' => User::query()->where('role', UserRole::Petugas)->count(),
                'supervisor' => User::query()->where('role', UserRole::Supervisor)->count(),
            ],
            'filters' => compact('search', 'role', 'status'),
            'roles' => UserRole::cases(),
        ];
    }

    /** @return list<string> */
    private function roleValues(): array
    {
        return array_map(
            fn (UserRole $role): string => $role->value,
            UserRole::cases(),
        );
    }

    private function redirectToIndex(Request $request): RedirectResponse
    {
        return redirect()->route($request->input('context') === 'mobile' ? 'mobile.users' : 'users.index');
    }
}
