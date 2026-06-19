<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Central list of seeders to run.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,         // seeds roles
            SuperAdminSeeder::class,   // seeds default super admin
        ]);
    }
}
