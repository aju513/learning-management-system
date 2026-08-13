<?php

namespace App\Http\Requests\LearningMaterial;

use App\Http\Requests\Concerns\AuthorizesLmsOwnership;

class UpdateLearningMaterialRequest extends StoreLearningMaterialRequest
{
    use AuthorizesLmsOwnership;

    public function authorize(): bool
    {
        return $this->canEditCourse($this->route('learning_material')->chapter->module->course, 'materials.edit');
    }

    public function rules(): array
    {
        return $this->materialRules(false);
    }
}
