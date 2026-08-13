<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_questions', function (Blueprint $table): void {
            $table->longText('reference_answer')->nullable()->after('prompt');
        });

        Schema::table('attempt_answers', function (Blueprint $table): void {
            $table->longText('text_answer')->nullable()->after('selected_option_ids');
            $table->text('reviewer_feedback')->nullable()->after('is_correct');
            $table->foreignId('reviewed_by')->nullable()->after('reviewer_feedback')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('attempt_answers', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['text_answer', 'reviewer_feedback', 'reviewed_at']);
        });

        Schema::table('assessment_questions', function (Blueprint $table): void {
            $table->dropColumn('reference_answer');
        });
    }
};
