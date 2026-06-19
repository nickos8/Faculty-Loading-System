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
        Schema::create('program_curriculums', function (Blueprint $table) {
        $table->id();
        $table->foreignId('program_id')->constrained()->onDelete('cascade'); // Foreign key to the programs table
        $table->integer('year');
        $table->integer('term');
        $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade'); // Foreign key to the subjects table
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('program_curriculums');
    }
};
