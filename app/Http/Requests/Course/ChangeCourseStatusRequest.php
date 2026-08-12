<?php

namespace App\Http\Requests\Course;

use App\Enums\CourseStatus;
use App\Http\Requests\Concerns\AuthorizesLmsOwnership;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeCourseStatusRequest extends FormRequest
{
    use AuthorizesLmsOwnership;

    public function authorize(): bool
    {
        return $this->canEditCourse($this->route('course'), 'courses.publish');
    }

    public function rules(): array
    {
        return ['status' => ['required', Rule::enum(CourseStatus::class)]];
    }
}
