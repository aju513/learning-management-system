<?php

namespace App\Http\Requests\Assessment;

use Illuminate\Foundation\Http\FormRequest;

class DownloadQuestionTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('assessments.import') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
