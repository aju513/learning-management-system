<?php

namespace App\Http\Requests\Assessment;

use App\Http\Requests\Concerns\AuthorizesLmsOwnership;
use Illuminate\Foundation\Http\FormRequest;

class DeleteAssessmentAssignmentRequest extends FormRequest
{
    use AuthorizesLmsOwnership;

    public function authorize(): bool
    {
        return $this->canEditAssessment($this->route('assessment_assignment')->assessment, 'assessments.assign');
    }

    public function rules(): array
    {
        return [];
    }
}
