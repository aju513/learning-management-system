<?php

namespace App\Http\Requests\LearningMaterial;

use App\Http\Requests\Concerns\AuthorizesLmsOwnership;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MoveLearningMaterialRequest extends FormRequest
{
    use AuthorizesLmsOwnership;

    public function authorize(): bool
    {
        return $this->canEditCourse($this->route('learning_material')->module->course, 'materials.reorder');
    }

    public function rules(): array
    {
        return ['direction' => ['required', Rule::in(['up', 'down'])]];
    }
}
