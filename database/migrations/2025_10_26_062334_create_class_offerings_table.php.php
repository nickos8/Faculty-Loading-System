<?php

// database/migrations/2025_10_26_000001_create_class_offerings_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('class_offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('sections'); // FK to your sections
            $table->foreignId('curriculum_term_subject_id')->constrained('curriculum_term_subjects'); // ties to required subject in this term
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['active','archived','cancelled'])->default('active');
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['section_id','status']);
            $table->index(['start_date','end_date']);
        });
    }
    public function down(): void { Schema::dropIfExists('class_offerings'); }
};
