<?php

namespace App\Http\Requests\Concerns;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\CourseAssessment;

trait AuthorizesLmsOwnership
{
    protected function canViewCourse(Course $course): bool
    {
        $user = $this->user();

        return (bool) ($user?->can('courses.show') && ($user->can('courses.view-all') || (int) $course->instructor_id === (int) $user->id));
    }

    protected function canEditCourse(Course $course, string $ability): bool
    {
        $user = $this->user();

        return (bool) ($user?->can($ability) && ($user->can('courses.edit-any') || (int) $course->instructor_id === (int) $user->id));
    }

    protected function canViewAssessment(Assessment $assessment): bool
    {
        $user = $this->user();

        return (bool) ($user?->can('assessments.show') && ($user->can('assessments.view-all') || (int) $assessment->created_by === (int) $user->id));
    }

    protected function canEditAssessment(Assessment $assessment, string $ability): bool
    {
        $user = $this->user();

        return (bool) ($user?->can($ability) && ($user->can('assessments.edit-any') || (int) $assessment->created_by === (int) $user->id));
    }

    protected function canEditCourseAssessment(CourseAssessment $assessment): bool
    {
        return $this->canEditCourse($assessment->material->chapter->module->course, 'course-assessments.questions.manage');
    }
}
