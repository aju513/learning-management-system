<?php

namespace App\Http\Requests\Course;

use App\Http\Requests\Concerns\AuthorizesLmsOwnership;
use Illuminate\Foundation\Http\FormRequest;

class PreviewCourseRequest extends FormRequest
{
    use AuthorizesLmsOwnership;

    public function authorize(): bool
    {
        return $this->canViewCourse($this->route('course'));
    }

    public function rules(): array
    {
        return [];
    }
}
