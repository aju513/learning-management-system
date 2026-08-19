<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseAssessmentAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_assessment_attempt_id', 'course_assessment_question_id', 'selected_option_ids', 'earned_marks', 'is_correct',
    ];

    protected function casts(): array
    {
        return ['selected_option_ids' => 'array', 'earned_marks' => 'decimal:2', 'is_correct' => 'boolean'];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(CourseAssessmentAttempt::class, 'course_assessment_attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(CourseAssessmentQuestion::class, 'course_assessment_question_id');
    }
}
