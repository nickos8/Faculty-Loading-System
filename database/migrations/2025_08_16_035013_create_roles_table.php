<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Create the "roles" table.
     * Each row is a role name we use in the app (e.g., super_admin).
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();                           // Primary key (auto-increment)
            $table->string('name')->unique();       // Role name (must be unique)
            $table->timestamps();                   // created_at, updated_at (for audit)
        });
    }

    /**
     * Drop the "roles" table (rollback).
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
