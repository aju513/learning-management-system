<?php

namespace App\Http\Requests\Concerns;

use App\Models\Assessment;
use App\Models\Course;

trait AuthorizesLmsOwnership
{
    protected function canViewCourse(Course $course): bool
    {
        $user = $this->user();

        return (bool) ($user?->can('courses.show') && ($user->can('courses.view-all') || $course->instructor_id === $user->id));
    }

    protected function canEditCourse(Course $course, string $ability): bool
    {
        $user = $this->user();

        return (bool) ($user?->can($ability) && ($user->can('courses.edit-any') || $course->instructor_id === $user->id));
    }

    protected function canViewAssessment(Assessment $assessment): bool
    {
        $user = $this->user();

        return (bool) ($user?->can('assessments.show') && ($user->can('assessments.view-all') || $assessment->created_by === $user->id));
    }

    protected function canEditAssessment(Assessment $assessment, string $ability): bool
    {
        $user = $this->user();

        return (bool) ($user?->can($ability) && ($user->can('assessments.edit-any') || $assessment->created_by === $user->id));
    }
}
