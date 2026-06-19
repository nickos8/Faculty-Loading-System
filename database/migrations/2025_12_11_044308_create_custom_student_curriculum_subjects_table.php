<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_student_curriculum_subjects', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_academic_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->foreignId('subject_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->unsignedTinyInteger('display_year_level')->nullable();
            $table->unsignedTinyInteger('display_term_no')->nullable();

            $table->enum('status', ['not_taken','enrolled','passed','failed','credited'])
                  ->default('not_taken');

            $table->text('remarks')->nullable();

            $table->enum('source_type', ['manual','transfer_credit','extra_subject'])
                  ->default('manual');

            $table->string('external_school')->nullable();
            $table->string('external_subject_code')->nullable();
            $table->string('external_subject_name')->nullable();
            $table->decimal('external_units', 5, 2)->nullable();

            $table->foreignId('evaluated_by')->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamp('evaluated_at')->nullable();

            $table->timestamps();

            $table->index(['student_academic_id', 'display_year_level', 'display_term_no'], 'idx_custom_sa_display');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_student_curriculum_subjects');
    }
};
