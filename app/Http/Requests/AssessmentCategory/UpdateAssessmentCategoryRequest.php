<?php

namespace App\Http\Requests\AssessmentCategory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssessmentCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('assessment-categories.edit') ?? false;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:150', Rule::unique('assessment_categories', 'name')->ignore($this->route('assessment_category'))], 'description' => ['nullable', 'string', 'max:2000'], 'is_active' => ['required', 'boolean']];
    }
}
