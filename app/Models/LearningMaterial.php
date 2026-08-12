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
        'course_module_id', 'assessment_id', 'title', 'type', 'description', 'content', 'external_url',
        'file_path', 'original_filename', 'mime_type', 'duration_minutes', 'position', 'is_required',
    ];

    protected function casts(): array
    {
        return ['type' => MaterialType::class, 'is_required' => 'boolean'];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'course_module_id');
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
