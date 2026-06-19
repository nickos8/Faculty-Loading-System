<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('curricula', function (Blueprint $t) {
            $t->id();
            $t->foreignId('program_id')->constrained()->cascadeOnDelete();
            $t->string('code');
            $t->string('title')->nullable();
            $t->date('effective_from')->nullable();
            $t->date('effective_to')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();

            // SHORT NAME
            $t->unique(['program_id','code'], 'curricula_prog_code_uq');
        });

        Schema::create('curriculum_terms', function (Blueprint $t) {
            $t->id();
            $t->foreignId('curriculum_id')->constrained('curricula')->cascadeOnDelete();
            $t->unsignedSmallInteger('year_level');
            $t->unsignedSmallInteger('term_no');         // 1..terms_per_year; 0 if summer
            $t->enum('term_type', ['regular','summer'])->default('regular');
            $t->unsignedSmallInteger('sequence');        // 1..N
            $t->timestamps();

            // SHORT NAME
            $t->unique(['curriculum_id','year_level','term_no','term_type'], 'cterms_curr_yr_term_uq');
        });

        Schema::create('curriculum_term_subjects', function (Blueprint $t) {
            $t->id();
            $t->foreignId('curriculum_term_id')->constrained('curriculum_terms')->cascadeOnDelete();
            $t->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $t->unsignedSmallInteger('units_override')->nullable();
            $t->enum('type', ['major','minor','elective','general','thesis','internship'])->nullable();
            $t->decimal('min_grade', 4, 2)->nullable();
            $t->boolean('is_required')->default(true);
            $t->string('note')->nullable();
            $t->timestamps();

            // SHORT NAME
            $t->unique(['curriculum_term_id','subject_id'], 'cts_term_subject_uq');
        });
    }

    public function down(): void {
        Schema::dropIfExists('curriculum_term_subjects');
        Schema::dropIfExists('curriculum_terms');
        Schema::dropIfExists('curricula');
    }
};
