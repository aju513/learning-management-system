<?php

namespace App\Http\Requests\Assessment;

use Illuminate\Foundation\Http\FormRequest;

class TakeAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('assessments.take') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
