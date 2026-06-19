<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Seed the master list of roles.
     * We use firstOrCreate so running seeder twice won't duplicate rows.
     */
    public function run(): void
    {
        foreach (['super_admin','program_admin','teacher','student'] as $name) {
            Role::firstOrCreate(['name' => $name]);
        }
    }
}
