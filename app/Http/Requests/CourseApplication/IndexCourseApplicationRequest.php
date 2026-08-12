<?php

namespace App\Http\Requests\CourseApplication;

use App\Enums\EnrollmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexCourseApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->can('course-applications.review-all') || $this->user()?->can('course-applications.review-owned'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'status' => ['nullable', Rule::in([EnrollmentStatus::Pending->value, EnrollmentStatus::Rejected->value])],
        ];
    }
}
