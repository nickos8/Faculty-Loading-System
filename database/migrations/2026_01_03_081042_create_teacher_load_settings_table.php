<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('teacher_load_settings', function (Blueprint $table) {
            $table->id();

            // only teachers will have a row here
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // teacher chooses this during registration
            $table->enum('employment_type', ['regular', 'part_time'])->default('regular');

            // program admin can adjust this (default depends on employment_type)
            $table->decimal('max_units', 5, 2)->default(36);

            $table->timestamps();

            // One settings row per teacher
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_load_settings');
    }
};
