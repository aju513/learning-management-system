<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $linkedAssessmentIds = DB::table('assessments')
            ->whereNotNull('course_id')
            ->orWhereNotNull('course_module_id')
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $materialAssessmentIds = DB::table('learning_materials')
            ->whereNotNull('assessment_id')
            ->pluck('assessment_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        DB::table('learning_materials')
            ->where('type', 'assessment')
            ->orWhereNotNull('assessment_id')
            ->delete();

        $assessmentIds = array_values(array_unique([...$linkedAssessmentIds, ...$materialAssessmentIds]));
        if ($assessmentIds !== []) {
            DB::table('assessments')->whereIn('id', $assessmentIds)->delete();
        }

        Schema::table('learning_materials', function (Blueprint $table): void {
            if (Schema::hasColumn('learning_materials', 'assessment_id')) {
                $table->dropForeign(['assessment_id']);
                $table->dropColumn('assessment_id');
            }
        });

        Schema::table('assessments', function (Blueprint $table): void {
            if (Schema::hasColumn('assessments', 'course_id')) {
                $table->dropForeign(['course_id']);
                $table->dropColumn('course_id');
            }
            if (Schema::hasColumn('assessments', 'course_module_id')) {
                $table->dropForeign(['course_module_id']);
                $table->dropColumn('course_module_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table): void {
            if (! Schema::hasColumn('assessments', 'course_id')) {
                $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('assessments', 'course_module_id')) {
                $table->foreignId('course_module_id')->nullable()->constrained()->nullOnDelete();
            }
        });

        Schema::table('learning_materials', function (Blueprint $table): void {
            if (! Schema::hasColumn('learning_materials', 'assessment_id')) {
                $table->foreignId('assessment_id')->nullable()->constrained()->nullOnDelete();
            }
        });
    }
};
