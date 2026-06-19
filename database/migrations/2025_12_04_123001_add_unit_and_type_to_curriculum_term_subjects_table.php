<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('curriculum_term_subjects', function (Blueprint $table) {
            // Add 'unit' if it doesn't exist
            if (! Schema::hasColumn('curriculum_term_subjects', 'unit')) {
                $table->unsignedTinyInteger('unit')
                      ->nullable()
                      ->after('subject_id');
            }

            // Add 'type' if it doesn't exist
            if (! Schema::hasColumn('curriculum_term_subjects', 'type')) {
                $table->string('type', 30)
                      ->nullable()
                      ->after('unit');
            }
        });
    }

    public function down(): void
    {
        Schema::table('curriculum_term_subjects', function (Blueprint $table) {
            if (Schema::hasColumn('curriculum_term_subjects', 'unit')) {
                $table->dropColumn('unit');
            }
            if (Schema::hasColumn('curriculum_term_subjects', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
