<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    // Drop the existing table (if it exists)
    Schema::dropIfExists('program_admin_assignments');

    // Create the new table
    Schema::create('program_admin_assignments', function (Blueprint $table) {
        $table->id(); // Primary key
        $table->foreignId('program_id')->constrained()->onDelete('cascade'); // Foreign key to the programs table
        $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Foreign key to the users table
        $table->timestamps(); // Timestamps for created_at and updated_at
    });
}

public function down()
{
    // Drop the table in case we need to rollback
    Schema::dropIfExists('program_admin_assignments');
}


};
