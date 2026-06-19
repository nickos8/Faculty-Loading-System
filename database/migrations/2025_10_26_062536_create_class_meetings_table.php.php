<?php

// database/migrations/2025_10_26_000002_create_class_meetings_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('class_meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_offering_id')->constrained('class_offerings')->cascadeOnDelete();
            $table->tinyInteger('day_of_week');         // 1=Mon … 7=Sun (pick and stick)
            $table->time('time_start');                 // half-open intervals [start, end)
            $table->time('time_end');
            $table->foreignId('teacher_id')->constrained('users'); // your teachers live in users+user_roles
            $table->foreignId('room_id')->constrained('rooms');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['teacher_id','day_of_week','time_start','time_end']);
            $table->index(['room_id','day_of_week','time_start','time_end']);
            $table->index(['class_offering_id','day_of_week']);
        });
    }
    public function down(): void { Schema::dropIfExists('class_meetings'); }
};
