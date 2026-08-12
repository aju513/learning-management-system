<?php

namespace App\Http\Requests\CourseCategory;

use Illuminate\Foundation\Http\FormRequest;

class DeleteCourseCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('course-categories.delete') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
