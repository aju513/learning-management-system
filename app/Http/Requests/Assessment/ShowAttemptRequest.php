<?php

namespace App\Http\Requests\Assessment;

use Illuminate\Foundation\Http\FormRequest;

class ShowAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->can('assessments.take') && (int) $this->route('assessment_attempt')->user_id === (int) $this->user()->id);
    }

    public function rules(): array
    {
        return [];
    }
}
