<?php

namespace App\Http\Requests\Learning;

use Illuminate\Foundation\Http\FormRequest;

class IndexLearningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('learning.view') ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
