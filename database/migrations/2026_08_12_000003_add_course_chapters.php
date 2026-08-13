<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('course_chapters')) {
            Schema::create('course_chapters', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('course_module_id')->constrained()->cascadeOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->unsignedInteger('position')->default(1);
                $table->timestamps();
                $table->index(['course_module_id', 'position']);
            });
        }

        if (! Schema::hasColumn('learning_materials', 'course_chapter_id')) {
            Schema::table('learning_materials', function (Blueprint $table): void {
                $table->foreignId('course_chapter_id')->nullable()->after('course_module_id')->constrained()->cascadeOnDelete();
            });
        }

        DB::table('course_modules')->orderBy('id')->each(function (object $module): void {
            $chapterId = DB::table('course_chapters')->where('course_module_id', $module->id)->value('id');
            if (! $chapterId) {
                $chapterId = DB::table('course_chapters')->insertGetId([
                    'course_module_id' => $module->id,
                    'title' => 'Chapter 1',
                    'description' => null,
                    'position' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('learning_materials')
                ->where('course_module_id', $module->id)
                ->whereNull('course_chapter_id')
                ->update(['course_chapter_id' => $chapterId]);
        });

        Schema::table('learning_materials', function (Blueprint $table): void {
            $table->unsignedBigInteger('course_chapter_id')->nullable(false)->change();
        });

        // MySQL may use the old composite index to support the course_module_id
        // foreign key. Remove the constraint before removing that index/column.
        if (Schema::hasColumn('learning_materials', 'course_module_id')) {
            Schema::table('learning_materials', function (Blueprint $table): void {
                $table->dropForeign(['course_module_id']);
            });

            if (Schema::hasIndex('learning_materials', 'learning_materials_course_module_id_position_index')) {
                Schema::table('learning_materials', function (Blueprint $table): void {
                    $table->dropIndex('learning_materials_course_module_id_position_index');
                });
            }

            Schema::table('learning_materials', function (Blueprint $table): void {
                $table->dropColumn('course_module_id');
            });
        }

        if (! Schema::hasIndex('learning_materials', 'learning_materials_course_chapter_id_position_index')) {
            Schema::table('learning_materials', function (Blueprint $table): void {
                $table->index(['course_chapter_id', 'position']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('learning_materials', function (Blueprint $table): void {
            $table->foreignId('course_module_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        DB::table('course_chapters')->orderBy('id')->each(function (object $chapter): void {
            DB::table('learning_materials')
                ->where('course_chapter_id', $chapter->id)
                ->update(['course_module_id' => $chapter->course_module_id]);
        });

        Schema::table('learning_materials', function (Blueprint $table): void {
            $table->unsignedBigInteger('course_module_id')->nullable(false)->change();
        });

        Schema::table('learning_materials', function (Blueprint $table): void {
            $table->dropForeign(['course_chapter_id']);
        });

        if (Schema::hasIndex('learning_materials', 'learning_materials_course_chapter_id_position_index')) {
            Schema::table('learning_materials', function (Blueprint $table): void {
                $table->dropIndex('learning_materials_course_chapter_id_position_index');
            });
        }

        Schema::table('learning_materials', function (Blueprint $table): void {
            $table->dropColumn('course_chapter_id');
            $table->index(['course_module_id', 'position']);
        });

        Schema::dropIfExists('course_chapters');
    }
};
