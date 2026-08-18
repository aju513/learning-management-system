<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseAssessmentOption extends Model
{
    use HasFactory;

    protected $fillable = ['course_assessment_question_id', 'option_text', 'is_correct', 'position'];

    protected function casts(): array
    {
        return ['is_correct' => 'boolean'];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(CourseAssessmentQuestion::class, 'course_assessment_question_id');
    }
}
