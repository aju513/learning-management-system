<?php

namespace App\Models;

use App\Enums\CreditAwardStatus;
use App\Enums\CreditSourceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditAward extends Model
{
    use HasFactory;

    protected $fillable = [
        'fiscal_year_id', 'user_id', 'source_type', 'source_id', 'source_key', 'course_id', 'assessment_id',
        'source_label', 'points', 'status', 'metadata', 'eligible_at', 'claimed_at',
    ];

    protected function casts(): array
    {
        return [
            'source_type' => CreditSourceType::class,
            'status' => CreditAwardStatus::class,
            'points' => 'decimal:2',
            'metadata' => 'array',
            'eligible_at' => 'datetime',
            'claimed_at' => 'datetime',
        ];
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function isClaimed(): bool
    {
        return $this->status === CreditAwardStatus::Claimed;
    }
}
