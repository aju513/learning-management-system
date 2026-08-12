<?php

namespace App\Http\Requests\Assessment;

class UpdateQuestionRequest extends StoreQuestionRequest
{
    public function authorize(): bool
    {
        $question = $this->route('assessment_question');

        return $this->canEditAssessment($question->assessment, 'assessments.edit');
    }
}
