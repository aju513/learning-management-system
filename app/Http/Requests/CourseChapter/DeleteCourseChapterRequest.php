<?php

namespace App\Http\Requests\CourseChapter;

use App\Http\Requests\Concerns\AuthorizesLmsOwnership;
use Illuminate\Foundation\Http\FormRequest;

class DeleteCourseChapterRequest extends FormRequest
{
    use AuthorizesLmsOwnership;

    public function authorize(): bool
    {
        return $this->canEditCourse($this->route('course_chapter')->module->course, 'chapters.delete');
    }

    public function rules(): array
    {
        return [];
    }
}
