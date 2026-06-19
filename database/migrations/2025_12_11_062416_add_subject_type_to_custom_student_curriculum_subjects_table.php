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
    Schema::table('custom_student_curriculum_subjects', function (Blueprint $table) {
        $table->enum('subject_type', [
            'major',
            'minor',
            'elective',
            'general',
            'thesis',
            'internship',
        ])->nullable()->after('external_units');
    });
}

public function down(): void
{
    Schema::table('custom_student_curriculum_subjects', function (Blueprint $table) {
        $table->dropColumn('subject_type');
    });
}

};
