<?php

namespace App\Http\Requests\CourseCategory;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('course-categories.create') ?? false;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:150', 'unique:course_categories,name'], 'description' => ['nullable', 'string', 'max:2000'], 'is_active' => ['required', 'boolean']];
    }
}
