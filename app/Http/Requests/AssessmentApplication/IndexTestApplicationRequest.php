<?php

namespace App\Http\Requests\AssessmentApplication;

use App\Enums\AssessmentApplicationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexTestApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->can('test-applications.review-all') || $this->user()?->can('test-applications.review-owned'));
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'assessment_id' => ['nullable', 'integer', 'exists:assessments,id'],
            'status' => ['nullable', Rule::enum(AssessmentApplicationStatus::class)],
        ];
    }
}
