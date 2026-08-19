<?php

namespace App\Models;

use App\Enums\CourseAssessmentQuestionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseAssessmentQuestion extends Model
{
    use HasFactory;

    protected $fillable = ['course_assessment_id', 'prompt', 'type', 'marks', 'position'];

    protected function casts(): array
    {
        return ['type' => CourseAssessmentQuestionType::class, 'marks' => 'decimal:2'];
    }

    public function courseAssessment(): BelongsTo
    {
        return $this->belongsTo(CourseAssessment::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(CourseAssessmentOption::class)->orderBy('position');
    }
}
