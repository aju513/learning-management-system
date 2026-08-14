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
            $type = $this->input('type');
            $videoSource = $this->input('video_source');

            if ($type === MaterialType::Video->value) {
                $hasVideo = $videoSource === 'url'
                    ? filled($this->input('video_url'))
                    : ($this->hasFile('file') || ($videoSource === 'upload' && $existingFile));
                if (! $hasVideo) {
                    $validator->errors()->add($videoSource === 'upload' ? 'file' : 'video_url', 'Provide the selected video source.');
                }
            }

            if ($this->hasFile('file')) {
                $extension = strtolower($this->file('file')->getClientOriginalExtension());
                if ($type === MaterialType::File->value && $extension !== $this->input('file_type')) {
                    $validator->errors()->add('file', 'The uploaded file must match the selected file type.');
                }
                if ($type === MaterialType::Video->value && ! in_array($extension, ['mp4', 'webm'], true)) {
                    $validator->errors()->add('file', 'Video uploads must be MP4 or WebM files.');
                }
            }
        }];
    }

    protected function materialRules(bool $creating): array
    {
        $fileTypes = [MaterialType::File->value];
        $material = $this->route('learning_material');
        $typeChanged = $material && $material->type->value !== $this->input('type');

        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(MaterialType::class)],
            'description' => ['nullable', 'string', 'max:2000'],
            'content' => [Rule::requiredIf($this->input('type') === 'article'), 'nullable', 'string', 'max:100000'],
            'video_source' => [Rule::requiredIf($this->input('type') === MaterialType::Video->value), 'nullable', Rule::in(['url', 'upload'])],
            'video_url' => ['nullable', 'url:http,https', 'max:2000'],
            'external_url' => [Rule::requiredIf($this->input('type') === MaterialType::Link->value), 'nullable', 'url:http,https', 'max:2000'],
            'assessment_id' => [Rule::requiredIf($this->input('type') === 'assessment'), 'nullable', 'integer', 'exists:assessments,id'],
            'file_type' => [Rule::requiredIf($this->input('type') === MaterialType::File->value), 'nullable', Rule::in(array_filter(['pdf', 'docx', 'pptx', $material?->file_type === 'legacy' ? 'legacy' : null]))],
            'file' => [Rule::requiredIf(($creating || $typeChanged) && (in_array($this->input('type'), $fileTypes, true) || ($this->input('type') === MaterialType::Video->value && $this->input('video_source') === 'upload'))), 'nullable', 'file', 'mimes:pdf,docx,pptx,mp4,webm', 'max:102400'],
            'duration_minutes' => ['nullable', 'integer', 'min:0', 'max:100000'], 'is_required' => ['required', 'boolean'],
        ];
    }
}
