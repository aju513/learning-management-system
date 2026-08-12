<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('courses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('course_categories')->nullOnDelete();
            $table->foreignId('instructor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('short_description', 500);
            $table->longText('description')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->string('difficulty', 20)->default('beginner');
            $table->unsignedInteger('estimated_duration_minutes')->default(0);
            $table->string('status', 20)->default('draft')->index();
            $table->string('navigation_mode', 20)->default('free');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['instructor_id', 'status']);
        });

        Schema::create('course_modules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('position')->default(1);
            $table->timestamps();
            $table->index(['course_id', 'position']);
        });

        Schema::create('assessments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('course_module_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->longText('instructions')->nullable();
            $table->unsignedInteger('duration_minutes')->default(30);
            $table->decimal('passing_percentage', 5, 2)->default(60);
            $table->unsignedSmallInteger('max_attempts')->default(1);
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('show_results')->default(true);
            $table->timestamps();
            $table->index(['created_by', 'status']);
        });

        Schema::create('assessment_questions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->text('prompt');
            $table->string('type', 30);
            $table->decimal('marks', 8, 2)->default(1);
            $table->unsignedInteger('position')->default(1);
            $table->timestamps();
            $table->index(['assessment_id', 'position']);
        });

        Schema::create('question_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('assessment_question_id')->constrained()->cascadeOnDelete();
            $table->text('option_text');
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('position')->default(1);
            $table->timestamps();
            $table->index(['assessment_question_id', 'position']);
        });

        Schema::create('learning_materials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_module_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('type', 30);
            $table->text('description')->nullable();
            $table->longText('content')->nullable();
            $table->text('external_url')->nullable();
            $table->string('file_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedInteger('duration_minutes')->default(0);
            $table->unsignedInteger('position')->default(1);
            $table->boolean('is_required')->default(true);
            $table->timestamps();
            $table->index(['course_module_id', 'position']);
        });

        Schema::create('enrollments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enrolled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('active')->index();
            $table->decimal('progress_percentage', 5, 2)->default(0);
            $table->timestamp('enrolled_at');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['course_id', 'user_id']);
        });

        Schema::create('material_progress', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learning_material_id')->constrained()->cascadeOnDelete();
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['enrollment_id', 'learning_material_id']);
        });

        Schema::create('assessment_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at');
            $table->timestamp('due_at')->nullable();
            $table->timestamps();
            $table->unique(['assessment_id', 'user_id']);
        });

        Schema::create('assessment_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('attempt_number');
            $table->string('status', 20)->default('in_progress')->index();
            $table->timestamp('started_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->decimal('earned_marks', 10, 2)->nullable();
            $table->decimal('total_marks', 10, 2)->nullable();
            $table->decimal('score_percentage', 5, 2)->nullable();
            $table->boolean('passed')->nullable();
            $table->timestamps();
            $table->unique(['assessment_id', 'user_id', 'attempt_number']);
        });

        Schema::create('attempt_answers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('assessment_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_question_id')->constrained()->cascadeOnDelete();
            $table->json('selected_option_ids')->nullable();
            $table->decimal('earned_marks', 8, 2)->default(0);
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
            $table->unique(['assessment_attempt_id', 'assessment_question_id'], 'attempt_answers_attempt_question_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempt_answers');
        Schema::dropIfExists('assessment_attempts');
        Schema::dropIfExists('assessment_assignments');
        Schema::dropIfExists('material_progress');
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('learning_materials');
        Schema::dropIfExists('question_options');
        Schema::dropIfExists('assessment_questions');
        Schema::dropIfExists('assessments');
        Schema::dropIfExists('course_modules');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('course_categories');
    }
};
