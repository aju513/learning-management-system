<?php

namespace App\Http\Requests\Assessment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAssessmentPlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('assessments.take') ?? false;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['all', 'pending', 'completed', 'failed', 'not_started'])],
            'sort' => ['nullable', Rule::in(['recent', 'title'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->input('status', 'all'),
            'sort' => $this->input('sort', 'recent'),
        ]);
    }
}
