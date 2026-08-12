<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttemptAnswer extends Model
{
    protected $fillable = [
        'assessment_attempt_id', 'assessment_question_id', 'selected_option_ids', 'earned_marks', 'is_correct',
    ];

    protected function casts(): array
    {
        return ['selected_option_ids' => 'array', 'earned_marks' => 'decimal:2', 'is_correct' => 'boolean'];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(AssessmentAttempt::class, 'assessment_attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(AssessmentQuestion::class, 'assessment_question_id');
    }
}
