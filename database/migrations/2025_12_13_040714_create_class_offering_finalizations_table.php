<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('class_offering_finalizations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('class_offering_id')
                ->constrained('class_offerings')
                ->cascadeOnDelete();

            // "Locked" info
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();

            // "Unlocked" audit trail
            $table->timestamp('unlocked_at')->nullable();
            $table->foreignId('unlocked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('unlock_reason')->nullable();

            $table->timestamps();

            // One row per offering (important)
            $table->unique('class_offering_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_offering_finalizations');
    }
};
