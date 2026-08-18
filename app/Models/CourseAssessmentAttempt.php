<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseAssessmentAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_assessment_id', 'user_id', 'attempt_number', 'status', 'started_at', 'submitted_at',
        'earned_marks', 'total_marks', 'score_percentage', 'passed',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime', 'submitted_at' => 'datetime',
            'earned_marks' => 'decimal:2', 'total_marks' => 'decimal:2', 'score_percentage' => 'decimal:2',
            'passed' => 'boolean',
        ];
    }

    public function courseAssessment(): BelongsTo
    {
        return $this->belongsTo(CourseAssessment::class);
    }

    public function trainee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(CourseAssessmentAnswer::class);
    }
}
