<?php

namespace App\Http\Requests\CourseModule;

use App\Http\Requests\Concerns\AuthorizesLmsOwnership;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MoveCourseModuleRequest extends FormRequest
{
    use AuthorizesLmsOwnership;

    public function authorize(): bool
    {
        return $this->canEditCourse($this->route('course_module')->course, 'modules.reorder');
    }

    public function rules(): array
    {
        return ['direction' => ['required', Rule::in(['up', 'down'])]];
    }
}
