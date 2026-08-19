<?php

namespace App\Http\Requests\LearningMaterial;

use App\Http\Requests\Concerns\AuthorizesLmsOwnership;
use App\Services\LearningMaterialImageService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLearningMaterialImageRequest extends FormRequest
{
    use AuthorizesLmsOwnership;

    public function authorize(): bool
    {
        return $this->canEditCourse($this->route('learning_material')->chapter->module->course, 'materials.edit');
    }

    public function rules(): array
    {
        return [
            'image' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp',
                'mimetypes:'.implode(',', LearningMaterialImageService::allowedMimeTypes()),
                'max:5120',
            ],
        ];
    }
}
