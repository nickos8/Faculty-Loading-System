<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_curriculum_subjects', function (Blueprint $table) {
            $table->id();

            // Which academic record
            $table->foreignId('student_academic_id')
                  ->constrained('student_academics')
                  ->cascadeOnDelete();

            // Which curriculum subject
            $table->foreignId('curriculum_term_subject_id')
                  ->constrained('curriculum_term_subjects')
                  ->cascadeOnDelete();

            // Optional: which class they used to take this subject
            $table->foreignId('class_offering_id')
                  ->nullable()
                  ->constrained('class_offerings')
                  ->nullOnDelete();

            // Optional: who evaluated
            $table->foreignId('evaluated_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // No grading, only status
            $table->enum('status', [
                'not_taken',   // default – in curriculum but never taken
                'enrolled',    // currently taking
                'passed',      // completed and passed
                'failed',      // completed and failed
                'credited',    // manually credited (transferee)
            ])->default('not_taken');

            $table->timestamp('evaluated_at')->nullable();
            $table->text('remarks')->nullable();

            $table->timestamps();

            // One row per (student_academic, curriculum subject)
            $table->unique(
                ['student_academic_id', 'curriculum_term_subject_id'],
                'u_student_curriculum_subject'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_curriculum_subjects');
    }
};
