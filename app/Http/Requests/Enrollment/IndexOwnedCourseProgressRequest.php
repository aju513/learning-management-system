<?php

namespace App\Http\Requests\Enrollment;

use Illuminate\Foundation\Http\FormRequest;

class IndexOwnedCourseProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('course-progress.view-owned') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
        ];
    }
}
