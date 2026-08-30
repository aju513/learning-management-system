<?php

namespace App\Http\Requests\Learning;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexLearningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('learning.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['all', 'in_progress', 'completed', 'not_started'])],
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
