<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Create the "user_roles" pivot table.
     * Links users ↔ roles (many-to-many).
     */
    public function up(): void
    {
        Schema::create('user_roles', function (Blueprint $table) {
            // Foreign key to users.id — deletes pivot rows if the user is deleted
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Foreign key to roles.id — deletes pivot rows if the role is deleted
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();

            // Composite primary key ensures no duplicate (user_id, role_id) pairs
            $table->primary(['user_id', 'role_id']);

            // Timestamps can help when auditing (“when did they get this role?”)
            $table->timestamps();

            // Optional indexes (composite PK already indexes both, but these help queries)
            $table->index('user_id');
            $table->index('role_id');
        });
    }

    /**
     * Drop the "user_roles" table (rollback).
     */
    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
