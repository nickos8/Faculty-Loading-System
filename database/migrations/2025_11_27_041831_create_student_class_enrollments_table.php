<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_class_enrollments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('class_offering_id')
                ->constrained('class_offerings')
                ->cascadeOnDelete();

            // per-class status
            $table->enum('status', ['enrolled', 'dropped', 'completed'])
                ->default('enrolled');

            // true if manually added / extra subject
            $table->boolean('is_additional')->default(false);

            $table->timestamps();

            $table->unique(['user_id', 'class_offering_id'], 'student_class_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_class_enrollments');
    }
};
