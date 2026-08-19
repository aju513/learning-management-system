<?php

namespace App\Http\Requests\CourseAssessment;

use Illuminate\Foundation\Http\FormRequest;

class ShowAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        $enrollment = $this->route('enrollment');
        $attempt = $this->route('course_assessment_attempt');
        $attempt->loadMissing('courseAssessment.material.chapter.module', 'trainee');

        return (bool) ($this->user()?->can('learning.view')
            && $enrollment->user_id === $this->user()->id
            && $enrollment->status->grantsLearningAccess()
            && $attempt->user_id === $this->user()->id
            && $attempt->courseAssessment->material->chapter->module->course_id === $enrollment->course_id);
    }

    public function rules(): array
    {
        return [];
    }
}
