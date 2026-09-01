<?php

namespace App\Http\Requests\AssessmentCategory;

use Illuminate\Foundation\Http\FormRequest;

class DeleteAssessmentCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('assessment-categories.delete') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
