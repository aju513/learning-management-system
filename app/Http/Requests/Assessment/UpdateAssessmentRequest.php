<?php

namespace App\Http\Requests\Assessment;

use App\Http\Requests\Concerns\AuthorizesLmsOwnership;

class UpdateAssessmentRequest extends StoreAssessmentRequest
{
    use AuthorizesLmsOwnership;

    public function authorize(): bool
    {
        return $this->canEditAssessment($this->route('assessment'), 'assessments.edit');
    }
}
