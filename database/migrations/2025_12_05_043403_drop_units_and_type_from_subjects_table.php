<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            // Drop columns now that units/type live in curriculum_term_subjects
            $table->dropColumn(['units', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            // Restore original columns in case you rollback
            $table->integer('units')->default(3);
            $table->enum('type', [
                'major', 'minor', 'elective', 'general', 'thesis', 'internship',
            ]);
        });
    }
};
