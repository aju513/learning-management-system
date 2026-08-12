<?php

namespace App\Models;

use App\Enums\AssessmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id', 'course_module_id', 'created_by', 'title', 'description', 'instructions', 'duration_minutes',
        'passing_percentage', 'max_attempts', 'status', 'starts_at', 'ends_at', 'show_results',
    ];

    protected function casts(): array
    {
        return [
            'status' => AssessmentStatus::class,
            'passing_percentage' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'show_results' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'course_module_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(AssessmentQuestion::class)->orderBy('position');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(AssessmentAssignment::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(AssessmentAttempt::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(LearningMaterial::class);
    }

    public function isAvailable(): bool
    {
        $now = now();

        return $this->status === AssessmentStatus::Published
            && (! $this->starts_at || $this->starts_at->lte($now))
            && (! $this->ends_at || $this->ends_at->gte($now));
    }
}
