<?php

namespace App\Http\Requests\Course;

use App\Http\Requests\Concerns\AuthorizesLmsOwnership;
use Illuminate\Foundation\Http\FormRequest;

class EditCourseRequest extends FormRequest
{
    use AuthorizesLmsOwnership;

    public function authorize(): bool
    {
        return $this->canEditCourse($this->route('course'), 'courses.edit');
    }

    public function rules(): array
    {
        return [];
    }
}
