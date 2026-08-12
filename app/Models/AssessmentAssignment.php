<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentAssignment extends Model
{
    protected $fillable = ['assessment_id', 'user_id', 'assigned_by', 'assigned_at', 'due_at'];

    protected function casts(): array
    {
        return ['assigned_at' => 'datetime', 'due_at' => 'datetime'];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function trainee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
