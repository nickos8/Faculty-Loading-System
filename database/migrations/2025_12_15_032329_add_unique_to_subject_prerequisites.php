<?php

// database/migrations/xxxx_add_unique_to_subject_prerequisites.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('subject_prerequisites', function (Blueprint $table) {
            $table->unique(['subject_id', 'prerequisite_subject_id'], 'subject_prereq_unique');
        });
    }

    public function down(): void
    {
        Schema::table('subject_prerequisites', function (Blueprint $table) {
            $table->dropUnique('subject_prereq_unique');
        });
    }
};

