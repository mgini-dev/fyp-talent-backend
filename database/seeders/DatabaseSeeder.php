<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Permissions
        $permissions = [
            ['name' => 'manage_users', 'display_name' => 'Manage Users', 'module' => 'System'],
            ['name' => 'manage_roles', 'display_name' => 'Manage Roles', 'module' => 'System'],
            ['name' => 'view_dashboard', 'display_name' => 'View Dashboard', 'module' => 'General'],
            ['name' => 'manage_talents', 'display_name' => 'Manage Talents', 'module' => 'Talent'],
            ['name' => 'manage_mentorship', 'display_name' => 'Manage Mentorship', 'module' => 'Mentorship'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm['name']], $perm);
        }

        // Create Roles
        $adminRole = Role::firstOrCreate(['name' => 'Admin'], ['display_name' => 'Administrator', 'description' => 'System Administrator']);
        $mentorRole = Role::firstOrCreate(['name' => 'Mentor'], ['display_name' => 'Mentor', 'description' => 'Mentors students']);
        $studentRole = Role::firstOrCreate(['name' => 'Student'], ['display_name' => 'Student', 'description' => 'System User / Student']);

        // Assign all permissions to Admin
        $adminRole->permissions()->sync(Permission::all());
        
        // Assign basic permissions to Student & Mentor
        $studentRole->permissions()->sync(Permission::whereIn('name', ['view_dashboard'])->get());
        $mentorRole->permissions()->sync(Permission::whereIn('name', ['view_dashboard', 'manage_mentorship'])->get());

        // Create Admin User
        $admin = User::firstOrCreate(
            ['email' => 'kuddo@udom.ac.tz'],
            [
                'first_name' => 'Kuddo',
                'last_name' => 'Mgonja',
                'password' => Hash::make('kuddo@123'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        // Assign Admin role
        if (!$admin->hasRole('Admin')) {
            $admin->roles()->attach($adminRole->id);
        }

        $this->call([
            TalentSeeder::class,
        ]);
    }
}
