<?php

namespace App\Http\Requests\Assessment;

use App\Http\Requests\Concerns\AuthorizesLmsOwnership;
use Illuminate\Foundation\Http\FormRequest;

class EditQuestionRequest extends FormRequest
{
    use AuthorizesLmsOwnership;

    public function authorize(): bool
    {
        return $this->canEditAssessment($this->route('assessment_question')->assessment, 'assessments.edit');
    }

    public function rules(): array
    {
        return [];
    }
}
