<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table): void {
            $table->timestamp('enrolled_at')->nullable()->change();
            $table->timestamp('requested_at')->nullable()->after('progress_percentage');
            $table->foreignId('reviewed_by')->nullable()->after('enrolled_by')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('enrolled_at');
            $table->text('review_note')->nullable()->after('reviewed_at');
            $table->index(['status', 'requested_at']);
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table): void {
            $table->dropIndex(['status', 'requested_at']);
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['requested_at', 'reviewed_at', 'review_note']);
            $table->timestamp('enrolled_at')->nullable(false)->change();
        });
    }
};
