<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('learning_materials')
            ->whereIn('id', DB::table('course_assessments')->select('learning_material_id'))
            ->update(['type' => 'course_assessment']);
    }

    public function down(): void
    {
        // The normalized type is the canonical representation and cannot be safely reverted.
    }
};
