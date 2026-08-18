<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_material_images', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('course_chapter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learning_material_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('disk', 50)->default('local');
            $table->string('path');
            $table->string('mime_type', 100);
            $table->string('original_filename')->nullable();
            $table->unsignedBigInteger('size');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index(['course_chapter_id', 'learning_material_id'], 'lmi_chapter_material_idx');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_material_images');
    }
};
