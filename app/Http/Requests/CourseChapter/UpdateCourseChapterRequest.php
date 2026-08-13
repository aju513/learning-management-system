<?php

namespace App\Http\Requests\CourseChapter;

class UpdateCourseChapterRequest extends StoreCourseChapterRequest
{
    public function authorize(): bool
    {
        return $this->canEditCourse($this->route('course_chapter')->module->course, 'chapters.edit');
    }
}
