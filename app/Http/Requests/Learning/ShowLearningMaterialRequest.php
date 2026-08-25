<?php

namespace App\Http\Requests\Learning;

use Illuminate\Foundation\Http\FormRequest;

class ShowLearningMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        $enrollment = $this->route('enrollment');
        $material = $this->route('learning_material');

        return (bool) ($this->user()?->can('learning.view')
            && $enrollment->course?->isPublished()
            && (int) $enrollment->user_id === (int) $this->user()->id
            && $enrollment->status->grantsLearningAccess()
            && (int) $material->chapter->module->course_id === (int) $enrollment->course_id);
    }

    public function rules(): array
    {
        return [];
    }
}
