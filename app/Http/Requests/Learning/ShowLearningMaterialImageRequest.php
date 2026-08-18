<?php

namespace App\Http\Requests\Learning;

use Illuminate\Foundation\Http\FormRequest;

class ShowLearningMaterialImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $image = $this->route('learning_material_image');
        $user = $this->user();

        if (! $user || ! $image) {
            return false;
        }

        if (! $image->learning_material_id) {
            return $image->uploaded_by === $user->id;
        }

        $course = $image->material->chapter->module->course;
        $canEdit = $user->can('materials.edit')
            && ($user->can('courses.edit-any') || $course->instructor_id === $user->id);
        $isEnrolled = $user->can('learning.view')
            && app(\App\Repositories\Contracts\EnrollmentRepositoryInterface::class)->findForCourseAndTrainee($course, $user) !== null;

        return $canEdit || $isEnrolled;
    }

    public function rules(): array
    {
        return [];
    }
}
