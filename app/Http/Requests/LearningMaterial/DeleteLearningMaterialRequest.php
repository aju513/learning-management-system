<?php

namespace App\Http\Requests\LearningMaterial;

use App\Http\Requests\Concerns\AuthorizesLmsOwnership;
use Illuminate\Foundation\Http\FormRequest;

class DeleteLearningMaterialRequest extends FormRequest
{
    use AuthorizesLmsOwnership;

    public function authorize(): bool
    {
        return $this->canEditCourse($this->route('learning_material')->module->course, 'materials.delete');
    }

    public function rules(): array
    {
        return [];
    }
}
