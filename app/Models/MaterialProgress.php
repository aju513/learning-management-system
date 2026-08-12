<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialProgress extends Model
{
    protected $table = 'material_progress';

    protected $fillable = ['enrollment_id', 'learning_material_id', 'last_viewed_at', 'completed_at'];

    protected function casts(): array
    {
        return ['last_viewed_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(LearningMaterial::class, 'learning_material_id');
    }
}
