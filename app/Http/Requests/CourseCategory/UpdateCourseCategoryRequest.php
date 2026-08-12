<?php

namespace App\Http\Requests\CourseCategory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('course-categories.edit') ?? false;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:150', Rule::unique('course_categories', 'name')->ignore($this->route('course_category'))], 'description' => ['nullable', 'string', 'max:2000'], 'is_active' => ['required', 'boolean']];
    }
}
