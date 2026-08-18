<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_assessments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('learning_material_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('passing_percentage', 5, 2)->default(60);
            $table->timestamps();
        });

        Schema::create('course_assessment_questions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_assessment_id')->constrained()->cascadeOnDelete();
            $table->text('prompt');
            $table->string('type', 30);
            $table->decimal('marks', 8, 2)->default(1);
            $table->unsignedInteger('position')->default(1);
            $table->timestamps();
            $table->index(['course_assessment_id', 'position'], 'ca_questions_position_index');
        });

        Schema::create('course_assessment_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_assessment_question_id')->constrained()->cascadeOnDelete();
            $table->text('option_text');
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('position')->default(1);
            $table->timestamps();
            $table->index(['course_assessment_question_id', 'position'], 'ca_options_position_index');
        });

        Schema::create('course_assessment_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('attempt_number');
            $table->string('status', 20)->default('in_progress')->index();
            $table->timestamp('started_at');
            $table->timestamp('submitted_at')->nullable();
            $table->decimal('earned_marks', 10, 2)->nullable();
            $table->decimal('total_marks', 10, 2)->nullable();
            $table->decimal('score_percentage', 5, 2)->nullable();
            $table->boolean('passed')->nullable();
            $table->timestamps();
            $table->index(['course_assessment_id', 'user_id', 'attempt_number'], 'ca_attempts_lookup_index');
        });

        Schema::create('course_assessment_answers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_assessment_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_assessment_question_id')->constrained()->cascadeOnDelete();
            $table->json('selected_option_ids')->nullable();
            $table->decimal('earned_marks', 8, 2)->default(0);
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
            $table->unique(['course_assessment_attempt_id', 'course_assessment_question_id'], 'course_assessment_answers_attempt_question_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_assessment_answers');
        Schema::dropIfExists('course_assessment_attempts');
        Schema::dropIfExists('course_assessment_options');
        Schema::dropIfExists('course_assessment_questions');
        Schema::dropIfExists('course_assessments');
    }
};
