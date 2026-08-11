<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('permission.table_names.permissions', 'permissions');

        Schema::table($tableName, function (Blueprint $table): void {
            $table->string('view_title')->nullable()->after('name');
            $table->text('description')->nullable()->after('view_title');
        });
    }

    public function down(): void
    {
        $tableName = config('permission.table_names.permissions', 'permissions');

        Schema::table($tableName, function (Blueprint $table): void {
            $table->dropColumn(['view_title', 'description']);
        });
    }
};
