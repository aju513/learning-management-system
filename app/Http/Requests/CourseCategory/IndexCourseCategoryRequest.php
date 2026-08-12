<?php

namespace App\Http\Requests\CourseCategory;

use Illuminate\Foundation\Http\FormRequest;

class IndexCourseCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('course-categories.manage') ?? false;
    }

    public function rules(): array
    {
        return ['search' => ['nullable', 'string', 'max:100']];
    }
}
