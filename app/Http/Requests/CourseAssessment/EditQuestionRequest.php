<?php

namespace App\Http\Requests\CourseAssessment;

use App\Http\Requests\Concerns\AuthorizesLmsOwnership;
use Illuminate\Foundation\Http\FormRequest;

class EditQuestionRequest extends FormRequest
{
    use AuthorizesLmsOwnership;

    public function authorize(): bool
    {
        $question = $this->route('course_assessment_question');

        return $question && $this->canEditCourseAssessment($question->courseAssessment);
    }

    public function rules(): array
    {
        return [];
    }
}
