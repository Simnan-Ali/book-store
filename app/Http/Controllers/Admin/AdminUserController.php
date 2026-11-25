<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $users = User::query()
            ->with('roles')
            ->when($filters['q'] ?? null, fn ($query, string $term) => $query->where(function ($query) use ($term): void {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            }))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'filters'));
    }

    public function create(): View
    {
        return view('admin.users.form', [
            'user' => new User,
            'roles' => $this->assignableRoles(),
            'selectedRoles' => [],
        ]);
    }

    public function store(AdminUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $roles = $data['roles'];
        unset($data['roles']);

        DB::transaction(function () use ($data, $roles): void {
            $user = User::create($data + ['is_admin' => false]);
            $user->syncRoles($roles);
        });

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user): View
    {
        abort_if($user->is(auth()->user()), 403);
        abort_if($user->is_admin || $user->roles()->where('roles.is_protected', true)->exists(), 403);
        $user->load('roles');

        return view('admin.users.form', [
            'user' => $user,
            'roles' => $this->assignableRoles(),
            'selectedRoles' => $user->roles->pluck('name')->all(),
        ]);
    }

    public function update(AdminUserRequest $request, User $user): RedirectResponse
    {
        abort_if($user->is($request->user()), 403);
        abort_if($user->is_admin || $user->roles()->where('roles.is_protected', true)->exists(), 403);

        $data = $request->validated();
        $roles = $data['roles'];
        unset($data['roles']);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        DB::transaction(function () use ($user, $data, $roles): void {
            $user->update($data);
            $user->syncRoles($roles);
        });

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->is(auth()->user()) || $user->is_admin || $user->roles()->where('roles.is_protected', true)->exists()) {
            return back()->with('error', 'Your account and protected administrator accounts cannot be deleted here.');
        }

        if ($user->products()->exists()) {
            return back()->with('error', 'This vendor still owns books. Reassign or remove those books before deleting the account.');
        }

        DB::transaction(function () use ($user): void {
            $user->syncRoles([]);
            $user->syncPermissions([]);
            $user->delete();
        });

        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }

    /**
     * @return Collection<int, Role>
     */
    private function assignableRoles(): Collection
    {
        return Role::query()
            ->where('guard_name', 'web')
            ->where('is_active', true)
            ->when(! auth()->user()->isSuperAdmin(), fn ($query) => $query->where('is_protected', false))
            ->orderBy('display_name')
            ->get();
    }
}
