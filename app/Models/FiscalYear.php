<?php

namespace App\Models;

use App\Enums\FiscalYearStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FiscalYear extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'starts_on', 'ends_on', 'status', 'attendance_threshold_days', 'attendance_credit_points',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'status' => FiscalYearStatus::class,
            'attendance_credit_points' => 'decimal:2',
        ];
    }

    public function awards(): HasMany
    {
        return $this->hasMany(CreditAward::class);
    }

    public function attendanceSnapshots(): HasMany
    {
        return $this->hasMany(AttendanceSnapshot::class);
    }

    public function isActive(): bool
    {
        return $this->status === FiscalYearStatus::Active;
    }
}
