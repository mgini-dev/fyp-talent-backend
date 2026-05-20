<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthService
{
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'status' => 'active',
            ]);

            $studentRole = Role::where('name', 'Student')->first();
            if ($studentRole) {
                $user->roles()->attach($studentRole->id);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'user' => $user->load(['roles.permissions', 'talents']),
                'token' => $token,
            ];
        });
    }

    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->status !== 'active') {
            throw new HttpException(403, 'Your account is currently ' . $user->status);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        $user->load(['roles.permissions', 'talents']);

        return [
            'user' => $user,
            'permissions' => $user->getAllPermissions(),
            'token' => $token,
        ];
    }

    public function getMentors()
    {
        return User::whereHas('roles', function($q) {
            $q->where('name', 'Mentor');
        })->with('talents:id,name,category')->get();
    }

    public function updateProfile(User $user, array $data): User
    {
        $user->update(array_intersect_key($data, array_flip(['first_name', 'last_name', 'phone', 'bio'])));

        return $user->fresh()->load('roles.permissions', 'talents');
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (!Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Current password is incorrect.'],
            ]);
        }

        $user->update(['password' => Hash::make($newPassword)]);
    }

    public function updatePhoto(User $user, $photoFile): string
    {
        $path = $photoFile->store('avatars', 'public');
        $url  = asset('storage/' . $path);

        $user->update(['profile_photo_url' => $url]);

        return $url;
    }
}
