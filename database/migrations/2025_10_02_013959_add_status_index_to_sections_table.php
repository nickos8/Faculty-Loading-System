<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Only add the index if the table exists
        if (Schema::hasTable('sections')) {
            Schema::table('sections', function (Blueprint $t) {
                // Name the index explicitly so we can drop it in down()
                $t->index(['program_id', 'status'], 'idx_sections_program_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sections')) {
            Schema::table('sections', function (Blueprint $t) {
                $t->dropIndex('idx_sections_program_status');
            });
        }
    }
};
