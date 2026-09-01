<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('assessments', function (Blueprint $table): void {
            $table->foreignId('category_id')->nullable()->after('id')->constrained('assessment_categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('category_id');
        });

        Schema::dropIfExists('assessment_categories');
    }
};
