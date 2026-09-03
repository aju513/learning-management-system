<?php

namespace App\Http\Requests\Assessment;

use Illuminate\Foundation\Http\FormRequest;

class ShowTraineeTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->can('test-applications.view-own') && $this->user()?->can('assessments.take'));
    }

    public function rules(): array
    {
        return [];
    }
}
