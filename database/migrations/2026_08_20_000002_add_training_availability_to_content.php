<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            $table->string('availability_scope', 20)->default('all')->after('credit_points')->index();
            $table->string('required_training_key')->nullable()->after('availability_scope')->index();
        });

        Schema::table('assessments', function (Blueprint $table): void {
            $table->string('availability_scope', 20)->default('all')->after('credit_points')->index();
            $table->string('required_training_key')->nullable()->after('availability_scope')->index();
        });
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table): void {
            $table->dropIndex(['required_training_key']);
            $table->dropIndex(['availability_scope']);
            $table->dropColumn(['required_training_key', 'availability_scope']);
        });

        Schema::table('courses', function (Blueprint $table): void {
            $table->dropIndex(['required_training_key']);
            $table->dropIndex(['availability_scope']);
            $table->dropColumn(['required_training_key', 'availability_scope']);
        });
    }
};
