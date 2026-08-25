<?php

namespace App\Models;

use App\Enums\AvailabilityScope;
use App\Enums\CourseStatus;
use App\Enums\NavigationMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id', 'instructor_id', 'title', 'slug', 'short_description', 'description',
        'thumbnail_path', 'difficulty', 'estimated_duration_minutes', 'credit_points', 'availability_scope', 'required_training_key', 'status', 'navigation_mode', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => CourseStatus::class,
            'navigation_mode' => NavigationMode::class,
            'credit_points' => 'decimal:2',
            'availability_scope' => AvailabilityScope::class,
            'published_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CourseCategory::class, 'category_id');
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function modules(): HasMany
    {
        return $this->hasMany(CourseModule::class)->orderBy('position');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function creditAwards(): HasMany
    {
        return $this->hasMany(CreditAward::class);
    }

    public function isPublished(): bool
    {
        return $this->status === CourseStatus::Published;
    }
}
