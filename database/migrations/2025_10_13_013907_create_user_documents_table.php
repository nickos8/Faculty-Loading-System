<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // optional label/category later (e.g., "enrollment_form")
            $t->string('kind', 50)->nullable();

            // file metadata
            $t->string('original_name');
            $t->string('mime', 100);
            $t->unsignedBigInteger('size');
            $t->string('path'); // storage path: user_docs/{user_id}/...

            $t->timestamps();

            $t->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_documents');
    }
};
