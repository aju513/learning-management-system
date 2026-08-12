<?php

namespace App\Http\Requests\Course;

use App\Enums\CourseStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('courses.manage') ?? false;
    }

    public function rules(): array
    {
        return ['search' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', Rule::enum(CourseStatus::class)], 'category_id' => ['nullable', 'integer', 'exists:course_categories,id']];
    }
}
