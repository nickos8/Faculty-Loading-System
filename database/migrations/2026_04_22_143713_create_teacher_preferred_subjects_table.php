<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('teacher_preferred_subjects', function (Blueprint $table) {
            $table->id();

            $table->foreignId('teacher_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('subject_id')
                ->constrained('subjects')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('preference_level')
                ->default(2)
                ->comment('1 = least preferred, 2 = preferred, 3 = most preferred');

            $table->timestamps();

            $table->unique(['teacher_id', 'subject_id'], 'teacher_subject_unique');
            $table->index(['subject_id', 'preference_level'], 'subject_preference_lookup_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_preferred_subjects');
    }
};
