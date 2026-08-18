<?php

namespace App\Http\Requests\CourseAssessment;

class UpdateQuestionRequest extends StoreQuestionRequest
{
    public function authorize(): bool
    {
        return $this->canEditCourseAssessment($this->route('course_assessment_question')->courseAssessment);
    }
}
