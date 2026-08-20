<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseAssessment extends Model
{
    use HasFactory;

    protected $fillable = ['learning_material_id', 'passing_percentage', 'credit_points'];

    protected function casts(): array
    {
        return ['passing_percentage' => 'decimal:2', 'credit_points' => 'decimal:2'];
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(LearningMaterial::class, 'learning_material_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(CourseAssessmentQuestion::class)->orderBy('position');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(CourseAssessmentAttempt::class);
    }
}
