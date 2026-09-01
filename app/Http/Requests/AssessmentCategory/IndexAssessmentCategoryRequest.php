<?php

namespace App\Http\Requests\AssessmentCategory;

use Illuminate\Foundation\Http\FormRequest;

class IndexAssessmentCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('assessment-categories.manage') ?? false;
    }

    public function rules(): array
    {
        return ['search' => ['nullable', 'string', 'max:100']];
    }
}
