<?php

namespace App\Http\Requests\Assessment;

use App\Http\Requests\Concerns\AuthorizesLmsOwnership;
use Illuminate\Foundation\Http\FormRequest;

class StoreAssessmentAssignmentRequest extends FormRequest
{
    use AuthorizesLmsOwnership;

    public function authorize(): bool
    {
        return $this->canEditAssessment($this->route('assessment'), 'assessments.assign');
    }

    public function rules(): array
    {
        return ['trainees' => ['required', 'array', 'min:1'], 'trainees.*' => ['integer', 'distinct', 'exists:users,id'], 'due_at' => ['nullable', 'date_format:Y-m-d\\TH:i', 'after:now']];
    }
}
