<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_academics', function (Blueprint $t) {
            $t->id(); // BIGINT UNSIGNED

            // who is this student?
            $t->foreignId('user_id')->constrained('users')->restrictOnDelete();

            // snapshot at approval time
            $t->foreignId('program_id')->constrained('programs')->restrictOnDelete();
            $t->foreignId('curriculum_id')->constrained('curricula')->restrictOnDelete();

            // regular students must have a section; irregular (later) can be NULL
            $t->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();

            $t->enum('enrollment_status', ['enrolled','dropped','graduated'])->default('enrolled');
            $t->enum('status', ['regular','irregular'])->default('regular');

            $t->timestamps();

            // one current placement per student
            $t->unique('user_id', 'u_student_academics_user');

            // helpful indexes for seat checks & rosters
            $t->index(['section_id','enrollment_status'], 'i_sa_section_enrollment');
            $t->index(['program_id','enrollment_status'], 'i_sa_program_enrollment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_academics');
    }
};
