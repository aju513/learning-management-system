<?php

namespace App\Http\Requests\Result;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('results.manage') ?? false;
    }

    public function rules(): array
    {
        return ['assessment_id' => ['nullable', 'integer', 'exists:assessments,id'], 'passed' => ['nullable', Rule::in(['0', '1', 0, 1])]];
    }
}
