<?php

namespace App\Http\Requests\Assessment;

use App\Enums\AssessmentStatus;
use App\Http\Requests\Concerns\AuthorizesLmsOwnership;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeAssessmentStatusRequest extends FormRequest
{
    use AuthorizesLmsOwnership;

    public function authorize(): bool
    {
        return $this->canEditAssessment($this->route('assessment'), 'assessments.publish');
    }

    public function rules(): array
    {
        return ['status' => ['required', Rule::enum(AssessmentStatus::class)]];
    }
}
