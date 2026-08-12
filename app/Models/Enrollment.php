<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id', 'user_id', 'enrolled_by', 'reviewed_by', 'status', 'progress_percentage',
        'requested_at', 'enrolled_at', 'reviewed_at', 'review_note', 'started_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => EnrollmentStatus::class,
            'progress_percentage' => 'decimal:2',
            'requested_at' => 'datetime',
            'enrolled_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function trainee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enrolled_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function materialProgress(): HasMany
    {
        return $this->hasMany(MaterialProgress::class);
    }
}
