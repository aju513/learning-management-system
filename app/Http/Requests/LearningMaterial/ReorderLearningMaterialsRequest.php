<?php

namespace App\Http\Requests\LearningMaterial;

use App\Http\Requests\Concerns\AuthorizesLmsOwnership;
use Illuminate\Foundation\Http\FormRequest;

class ReorderLearningMaterialsRequest extends FormRequest
{
    use AuthorizesLmsOwnership;

    public function authorize(): bool
    {
        return $this->canEditCourse($this->route('course_chapter')->module->course, 'materials.reorder');
    }

    public function rules(): array
    {
        return [
            'material_ids' => ['required', 'array', 'min:1'],
            'material_ids.*' => ['required', 'integer', 'distinct'],
        ];
    }
}
