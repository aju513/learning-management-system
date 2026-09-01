<?php

namespace App\Http\Requests\AssessmentCategory;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssessmentCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('assessment-categories.create') ?? false;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:150', 'unique:assessment_categories,name'], 'description' => ['nullable', 'string', 'max:2000'], 'is_active' => ['required', 'boolean']];
    }
}
