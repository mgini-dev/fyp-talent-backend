<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UserService
{
    public function getUsers(): Collection
    {
        return User::with(['roles', 'talents'])->get();
    }

    public function updateUserStatus(User $me, int $id, string $status): User
    {
        $user = User::findOrFail($id);

        if ($user->id === $me->id && $status === 'suspended') {
            throw new HttpException(400, 'You cannot suspend your own account');
        }

        $user->update(['status' => $status]);

        return $user;
    }

    public function assignRole(int $id, int $roleId): User
    {
        $user = User::findOrFail($id);
        $role = Role::findOrFail($roleId);

        return DB::transaction(function () use ($user, $role) {
            $user->roles()->sync([$role->id]);
            return $user->load('roles');
        });
    }

    public function getRoles(): Collection
    {
        return Role::with('permissions')->get();
    }

    public function getPermissions(): Collection
    {
        return Permission::all();
    }

    public function updateRolePermissions(int $id, array $permissionIds): Role
    {
        $role = Role::findOrFail($id);

        return DB::transaction(function () use ($role, $permissionIds) {
            $role->permissions()->sync($permissionIds);
            return $role->load('permissions');
        });
    }

    public function createUser(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'status' => 'active',
                'bio' => $data['bio'] ?? null,
                'email_verified_at' => now(),
            ]);

            $user->roles()->attach($data['role_id']);

            return $user->load('roles');
        });
    }

    public function storeRole(array $data): Role
    {
        return DB::transaction(function () use ($data) {
            return Role::create([
                'name'         => $data['name'],
                'display_name' => $data['display_name'],
                'description'  => $data['description'] ?? null,
            ]);
        });
    }

    public function updateRole(int $id, array $data): Role
    {
        $role = Role::findOrFail($id);

        return DB::transaction(function () use ($role, $data) {
            $role->update([
                'name'         => $data['name'],
                'display_name' => $data['display_name'],
                'description'  => $data['description'] ?? null,
            ]);
            return $role;
        });
    }

    public function deleteRole(int $id): void
    {
        $role = Role::findOrFail($id);

        if (strtolower($role->name) === 'admin') {
            throw new HttpException(403, 'The Admin role cannot be deleted.');
        }

        DB::transaction(function () use ($role) {
            $role->permissions()->detach();
            $role->delete();
        });
    }

    public function storePermission(array $data): Permission
    {
        return DB::transaction(function () use ($data) {
            return Permission::create([
                'name'         => $data['name'],
                'display_name' => $data['display_name'],
                'module'       => $data['module'],
                'description'  => $data['description'] ?? null,
            ]);
        });
    }

    public function updatePermission(int $id, array $data): Permission
    {
        $permission = Permission::findOrFail($id);

        return DB::transaction(function () use ($permission, $data) {
            $permission->update([
                'name'         => $data['name'],
                'display_name' => $data['display_name'],
                'module'       => $data['module'],
                'description'  => $data['description'] ?? null,
            ]);
            return $permission;
        });
    }

    public function deletePermission(int $id): void
    {
        $permission = Permission::findOrFail($id);

        DB::transaction(function () use ($permission) {
            $permission->roles()->detach();
            $permission->delete();
        });
    }
}
