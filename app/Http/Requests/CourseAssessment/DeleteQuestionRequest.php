<?php

namespace App\Http\Requests\CourseAssessment;

use App\Http\Requests\Concerns\AuthorizesLmsOwnership;
use Illuminate\Foundation\Http\FormRequest;

class DeleteQuestionRequest extends FormRequest
{
    use AuthorizesLmsOwnership;

    public function authorize(): bool
    {
        return $this->canEditCourseAssessment($this->route('course_assessment_question')->courseAssessment);
    }

    public function rules(): array
    {
        return [];
    }
}
