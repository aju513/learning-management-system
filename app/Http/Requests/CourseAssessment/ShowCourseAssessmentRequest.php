<?php

namespace App\Http\Requests\CourseAssessment;

use App\Http\Requests\Concerns\AuthorizesLmsOwnership;
use Illuminate\Foundation\Http\FormRequest;

class ShowCourseAssessmentRequest extends FormRequest
{
    use AuthorizesLmsOwnership;

    public function authorize(): bool
    {
        return $this->canEditCourseAssessment($this->route('course_assessment'));
    }

    public function rules(): array
    {
        return [];
    }
}
