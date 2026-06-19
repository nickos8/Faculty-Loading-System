<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Create the "user_approvals" audit table.
     * Each row = one decision made ABOUT a user BY an admin.
     */
    public function up()
{
    Schema::create('user_approvals', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // The user being approved/rejected
        $table->foreignId('acted_by')->constrained('users')->onDelete('cascade'); // The admin who approved/rejected
        $table->enum('decision', ['approved', 'declined']); // Approval decision
        $table->string('note')->nullable(); // Optional note about the decision
        $table->timestamps(); // created_at and updated_at
    });
}


    /**
     * Drop the "user_approvals" table (rollback).
     */
    public function down(): void
    {
        Schema::dropIfExists('user_approvals');
    }
};
