<?php

namespace App\Http\Requests\CourseChapter;

use App\Http\Requests\Concerns\AuthorizesLmsOwnership;
use Illuminate\Foundation\Http\FormRequest;

class ReorderCourseChaptersRequest extends FormRequest
{
    use AuthorizesLmsOwnership;

    public function authorize(): bool
    {
        return $this->canEditCourse($this->route('course_module')->course, 'chapters.reorder');
    }

    public function rules(): array
    {
        return [
            'chapter_ids' => ['required', 'array', 'min:1'],
            'chapter_ids.*' => ['required', 'integer', 'distinct'],
        ];
    }
}
