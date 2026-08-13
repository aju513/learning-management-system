<?php

namespace App\Http\Requests\LearningMaterial;

use App\Enums\MaterialType;
use App\Http\Requests\Concerns\AuthorizesLmsOwnership;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreLearningMaterialRequest extends FormRequest
{
    use AuthorizesLmsOwnership;

    public function authorize(): bool
    {
        return $this->canEditCourse($this->route('course_chapter')->module->course, 'materials.create');
    }

    public function rules(): array
    {
        return $this->materialRules(true);
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $material = $this->route('learning_material');
            $existingFile = $material && $material->type->value === $this->input('type') ? $material->file_path : null;
            if ($this->input('type') === MaterialType::Video->value && blank($this->input('external_url')) && ! $this->hasFile('file') && ! $existingFile) {
                $validator->errors()->add('external_url', 'Provide a video URL or upload a video file.');
            }
        }];
    }

    protected function materialRules(bool $creating): array
    {
        $fileTypes = ['pdf', 'ppt', 'pptx', 'doc', 'docx', 'downloadable_file'];
        $material = $this->route('learning_material');
        $typeChanged = $material && $material->type->value !== $this->input('type');

        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(MaterialType::class), Rule::notIn(($creating || $material?->type !== MaterialType::Assessment) ? [MaterialType::Assessment->value] : [])],
            'description' => ['nullable', 'string', 'max:2000'],
            'content' => [Rule::requiredIf($this->input('type') === 'article'), 'nullable', 'string', 'max:100000'],
            'external_url' => [Rule::requiredIf(in_array($this->input('type'), ['external_link'], true)), 'nullable', 'url:http,https', 'max:2000'],
            'assessment_id' => [Rule::requiredIf($this->input('type') === 'assessment'), 'nullable', 'integer', 'exists:assessments,id'],
            'file' => [Rule::requiredIf(($creating || $typeChanged) && in_array($this->input('type'), $fileTypes, true)), 'nullable', 'file', 'mimes:pdf,ppt,pptx,doc,docx,zip,mp4,webm', 'max:102400'],
            'duration_minutes' => ['nullable', 'integer', 'min:0', 'max:100000'], 'is_required' => ['required', 'boolean'],
        ];
    }
}
