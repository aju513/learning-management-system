<?php

namespace App\Http\Requests\CourseModule;

use App\Http\Requests\Concerns\AuthorizesLmsOwnership;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseModuleRequest extends FormRequest
{
    use AuthorizesLmsOwnership;

    public function authorize(): bool
    {
        return $this->canEditCourse($this->route('course_module')->course, 'modules.edit');
    }

    public function rules(): array
    {
        return ['title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:2000']];
    }
}
