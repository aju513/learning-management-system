<?php

namespace App\Http\Requests\Assessment;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('assessments.create') ?? false;
    }

    public function rules(): array
    {
        return $this->assessmentRules();
    }

    protected function assessmentRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:3000'],
            'instructions' => ['nullable', 'string', 'max:10000'], 'duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'passing_percentage' => ['required', 'numeric', 'min:0', 'max:100'], 'max_attempts' => ['required', 'integer', 'min:1', 'max:20'],
            'starts_at' => ['nullable', 'date'], 'ends_at' => ['nullable', 'date', 'after:starts_at'], 'show_results' => ['required', 'boolean'],
        ];
    }
}
