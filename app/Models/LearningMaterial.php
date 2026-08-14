<?php

namespace App\Models;

use App\Enums\MaterialType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_chapter_id', 'assessment_id', 'title', 'type', 'description', 'content', 'video_url', 'external_url', 'file_type',
        'file_path', 'original_filename', 'mime_type', 'duration_minutes', 'position', 'is_required',
    ];

    protected function casts(): array
    {
        return ['type' => MaterialType::class, 'is_required' => 'boolean'];
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(CourseChapter::class, 'course_chapter_id');
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(MaterialProgress::class);
    }
}
