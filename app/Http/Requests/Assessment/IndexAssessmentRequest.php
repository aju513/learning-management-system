<?php

namespace App\Http\Requests\Assessment;

use App\Enums\AssessmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('assessments.manage') ?? false;
    }

    public function rules(): array
    {
        return ['search' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', Rule::enum(AssessmentStatus::class)]];
    }
}
