<?php

namespace App\Http\Requests\CourseAssessment;

class SubmitAttemptRequest extends ShowAttemptRequest
{
    public function authorize(): bool
    {
        return parent::authorize() && (bool) $this->user()?->can('learning.complete');
    }

    public function rules(): array
    {
        return [
            'answers' => ['nullable', 'array'],
            'answers.*' => ['nullable'],
            'answers.*.*' => ['integer'],
        ];
    }
}
