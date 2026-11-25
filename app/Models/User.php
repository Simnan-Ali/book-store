<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'is_admin', 'avatar_path'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'vendor_id');
    }

    public function hasActiveRole(string|array $roleNames): bool
    {
        return $this->roles()
            ->where('roles.guard_name', 'web')
            ->where('roles.is_active', true)
            ->whereIn('roles.name', (array) $roleNames)
            ->exists();
    }

    public function hasActiveRoles(): bool
    {
        return $this->roles()
            ->where('roles.guard_name', 'web')
            ->where('roles.is_active', true)
            ->exists();
    }

    public function hasAdminPortalAccess(): bool
    {
        return $this->hasActiveRoles()
            || ($this->is_admin && ! $this->roles()->where('roles.guard_name', 'web')->exists());
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasActiveRole('super-admin')
            || ($this->is_admin && ! $this->roles()->where('roles.guard_name', 'web')->exists());
    }

    public function hasPermissionTo($permission, ?string $guardName = null): bool
    {
        if ($this->getWildcardClass()) {
            return $this->hasWildcardPermission($permission, $guardName);
        }

        $permission = $this->filterPermission($permission, $guardName);

        if ($this->hasDirectPermission($permission)) {
            return true;
        }

        return $this->roles()
            ->where('roles.guard_name', 'web')
            ->where('roles.is_active', true)
            ->whereHas('permissions', fn ($query) => $query->whereKey($permission->getKey()))
            ->exists();
    }

    public function effectivePermissions(): Collection
    {
        $rolePermissions = $this->roles()
            ->where('roles.guard_name', 'web')
            ->where('roles.is_active', true)
            ->with('permissions')
            ->get()
            ->flatMap(fn (Role $role) => $role->permissions);

        return $this->getDirectPermissions()
            ->merge($rolePermissions)
            ->unique('id')
            ->sortBy(fn (Permission $permission): string => ($permission->module ?? '').'|'.$permission->name)
            ->values();
    }

    public function primaryRoleDisplayName(): string
    {
        if ($this->isSuperAdmin()) {
            return 'Super Admin';
        }

        return $this->roles()
            ->where('roles.guard_name', 'web')
            ->where('roles.is_active', true)
            ->orderBy('roles.display_name')
            ->value('roles.display_name') ?? 'Administrator';
    }
}
