<?php

namespace Database\Factories;

use App\Enums\AssessmentApplicationStatus;
use App\Models\Assessment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssessmentApplicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'assessment_id' => Assessment::factory()->published(),
            'user_id' => User::factory(),
            'status' => AssessmentApplicationStatus::Pending,
            'requested_at' => now(),
            'reviewed_by' => null,
            'reviewed_at' => null,
            'review_note' => null,
        ];
    }
}
