<?php

namespace App\Http\Requests\CourseModule;

use App\Http\Requests\Concerns\AuthorizesLmsOwnership;
use Illuminate\Foundation\Http\FormRequest;

class DeleteCourseModuleRequest extends FormRequest
{
    use AuthorizesLmsOwnership;

    public function authorize(): bool
    {
        return $this->canEditCourse($this->route('course_module')->course, 'modules.delete');
    }

    public function rules(): array
    {
        return [];
    }
}
