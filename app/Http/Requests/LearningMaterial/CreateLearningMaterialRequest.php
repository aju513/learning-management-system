<?php

namespace App\Http\Requests\LearningMaterial;

use App\Http\Requests\Concerns\AuthorizesLmsOwnership;
use Illuminate\Foundation\Http\FormRequest;

class CreateLearningMaterialRequest extends FormRequest
{
    use AuthorizesLmsOwnership;

    public function authorize(): bool
    {
        return $this->canEditCourse($this->route('course_chapter')->module->course, 'materials.create');
    }

    public function rules(): array
    {
        return [];
    }
}
