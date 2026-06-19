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
        Schema::table('programs', function (Blueprint $table) {
        $table->integer('terms_per_year')->default(2); // Adding default 2 terms per year
    
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('programs', function (Blueprint $table) {
        $table->dropColumn('terms_per_year');
        });
    }
};
