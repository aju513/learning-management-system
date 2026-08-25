<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            $table->decimal('credit_points', 8, 2)->default(0)->after('estimated_duration_minutes');
        });

        Schema::table('assessments', function (Blueprint $table): void {
            $table->decimal('credit_points', 8, 2)->default(0)->after('passing_percentage');
        });

        Schema::table('course_assessments', function (Blueprint $table): void {
            $table->decimal('credit_points', 8, 2)->default(0)->after('passing_percentage');
        });

        Schema::create('fiscal_years', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status', 20)->default('draft')->index();
            $table->unsignedInteger('attendance_threshold_days')->default(0);
            $table->decimal('attendance_credit_points', 8, 2)->default(0);
            $table->timestamps();
            $table->unique(['starts_on', 'ends_on']);
            $table->index(['starts_on', 'ends_on']);
        });

        Schema::create('attendance_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('present_days')->default(0);
            $table->string('source', 50)->default('sandbox');
            $table->string('status', 20)->default('success');
            $table->text('error_message')->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();
            $table->unique(['fiscal_year_id', 'user_id']);
        });

        Schema::create('credit_awards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('source_type', 30);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_key', 120);
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assessment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_label');
            $table->decimal('points', 8, 2);
            $table->string('status', 20)->default('eligible')->index();
            $table->json('metadata')->nullable();
            $table->timestamp('eligible_at');
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();
            $table->unique(['fiscal_year_id', 'user_id', 'source_key']);
            $table->index(['user_id', 'fiscal_year_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_awards');
        Schema::dropIfExists('attendance_snapshots');
        Schema::dropIfExists('fiscal_years');
        Schema::table('course_assessments', fn (Blueprint $table) => $table->dropColumn('credit_points'));
        Schema::table('assessments', fn (Blueprint $table) => $table->dropColumn('credit_points'));
        Schema::table('courses', fn (Blueprint $table) => $table->dropColumn('credit_points'));
    }
};
