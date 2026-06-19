<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('programs', function (Blueprint $table) {
        $table->id(); // auto-increment id
        $table->string('program_code')->unique(); // unique program code (e.g., BSIT)
        $table->string('program_name'); // name of the program (e.g., Bachelor of Science in IT)
        $table->text('description')->nullable(); // optional description of the program
        $table->enum('status', ['active', 'inactive'])->default('inactive'); // status of the program
        $table->integer('duration')->nullable(); // duration of the program (in years, e.g., 4 years)
        $table->timestamps(); // created_at and updated_at timestamps
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};
