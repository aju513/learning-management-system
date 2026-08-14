<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_materials', function (Blueprint $table): void {
            $table->string('file_type', 30)->nullable()->after('type');
            $table->text('video_url')->nullable()->after('content');
        });

        DB::table('learning_materials')->orderBy('id')->each(function (object $material): void {
            $type = match ($material->type) {
                'external_link' => 'link',
                'pdf', 'docx', 'pptx' => 'file',
                'ppt', 'doc', 'downloadable_file' => 'file',
                default => $material->type,
            };

            $fileType = match ($material->type) {
                'pdf' => 'pdf',
                'docx' => 'docx',
                'pptx' => 'pptx',
                'ppt', 'doc', 'downloadable_file' => $this->legacyFileType($material->original_filename),
                default => null,
            };

            $updates = ['type' => $type, 'file_type' => $fileType];
            if ($material->type === 'video' && filled($material->external_url)) {
                $updates['video_url'] = $material->external_url;
                $updates['external_url'] = null;
            }

            DB::table('learning_materials')->where('id', $material->id)->update($updates);
        });
    }

    public function down(): void
    {
        DB::table('learning_materials')->orderBy('id')->each(function (object $material): void {
            $type = match ([$material->type, $material->file_type]) {
                ['link', null] => 'external_link',
                ['file', 'pdf'] => 'pdf',
                ['file', 'docx'] => 'docx',
                ['file', 'pptx'] => 'pptx',
                ['file', 'legacy'] => 'downloadable_file',
                default => $material->type,
            };

            $updates = ['type' => $type];
            if ($material->type === 'video' && filled($material->video_url)) {
                $updates['external_url'] = $material->video_url;
            }

            DB::table('learning_materials')->where('id', $material->id)->update($updates);
        });

        Schema::table('learning_materials', function (Blueprint $table): void {
            $table->dropColumn(['file_type', 'video_url']);
        });
    }

    private function legacyFileType(?string $filename): string
    {
        return match (strtolower(pathinfo((string) $filename, PATHINFO_EXTENSION))) {
            'pdf' => 'pdf',
            'docx' => 'docx',
            'pptx' => 'pptx',
            default => 'legacy',
        };
    }
};
