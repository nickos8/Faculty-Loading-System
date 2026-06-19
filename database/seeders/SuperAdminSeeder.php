<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Ensure we have an active Super Admin user with the proper role.
     * - updateOrCreate: updates existing user or creates a new one
     * - assignRole: idempotent (won't duplicate pivot)
     */
    public function run(): void
    {
        // 1) Make sure the roles exist
        foreach ([Role::SUPER_ADMIN, Role::PROGRAM_ADMIN, Role::TEACHER, Role::STUDENT] as $name) {
            Role::firstOrCreate(['name' => $name]);
        }

        // 2) Create or update the Super Admin account
        $user = User::updateOrCreate(
            ['email' => 'super@granby.local'],   // unique key to find the user
            [
                'name'       => 'Super Admin',   // safe to change later in UI
                // Only set a new password if we’re creating; if updating and you want to keep old,
                // you can remove this line. Keeping it here to guarantee access.
                'password'   => Hash::make('Password123!'),
                'status'     => 'active',        // <-- force active
                'approved_by'=> null,            // optional: clear any flags
                'approved_at'=> now(),           // optional: mark approved time
                'declined_by'=> null,
                'declined_at'=> null,
            ]
        );

        // 3) Ensure the super_admin role is attached (no duplicates)
        $user->assignRole(Role::SUPER_ADMIN);
    }
}
