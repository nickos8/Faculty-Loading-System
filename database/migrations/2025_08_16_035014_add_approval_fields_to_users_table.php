<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Add approval-related fields to "users".
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Optional program the user belongs to (no FK yet; we’ll add when Programs exist)
            // Placed after remember_token just to keep columns tidy
            $table->unsignedBigInteger('program_id')->nullable()->after('remember_token');

            // Current approval status of the user’s account
            // - pending: just registered, waiting for approval
            // - active: approved and allowed to use the system
            // - inactive: temporarily disabled by admin
            // - declined: rejected (can’t use the system)
            $table->enum('status', ['pending','active','inactive','declined'])
                  ->default('pending')
                  ->after('program_id');

            // Who approved this user? (self-referencing FK to users.id)
            $table->foreignId('approved_by')->nullable()->after('status')
                  ->constrained('users')->nullOnDelete();

            // When were they approved?
            $table->timestamp('approved_at')->nullable()->after('approved_by');

            // Who declined this user? (self-referencing FK to users.id)
            $table->foreignId('declined_by')->nullable()->after('approved_at')
                  ->constrained('users')->nullOnDelete();

            // When were they declined?
            $table->timestamp('declined_at')->nullable()->after('declined_by');

            // Helpful indexes for common filtering
            $table->index('status');
            $table->index('program_id');
        });
    }

    /**
     * Remove the approval-related fields (rollback).
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['status']);
            $table->dropIndex(['program_id']);

            // Drop foreign keys before dropping the columns
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['declined_by']);

            // Remove columns in reverse-ish order (not strictly required, just neat)
            $table->dropColumn(['declined_at', 'declined_by', 'approved_at', 'approved_by', 'status', 'program_id']);
        });
    }
};
