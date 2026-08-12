<?php

namespace App\Models;

use App\Enums\AttemptStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentAttempt extends Model
{
    protected $fillable = [
        'assessment_id', 'user_id', 'attempt_number', 'status', 'started_at', 'expires_at', 'submitted_at',
        'earned_marks', 'total_marks', 'score_percentage', 'passed',
    ];

    protected function casts(): array
    {
        return [
            'status' => AttemptStatus::class,
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'submitted_at' => 'datetime',
            'earned_marks' => 'decimal:2',
            'total_marks' => 'decimal:2',
            'score_percentage' => 'decimal:2',
            'passed' => 'boolean',
        ];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function trainee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class);
    }
}
